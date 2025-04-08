package main

import (
	"archive/zip"
	"encoding/json"
	"errors"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"
)

const (
	baseDir  = ".."
	dataDir  = "../temp"
	pinnedFile = dataDir + "/pinned_folders.txt"
	recentFile = dataDir + "/recent_folders.txt"
	maxListSize = 30
)

type FolderInfo struct {
	Name     string `json:"name"`
	Modified int64  `json:"modified"`
	IsPinned bool   `json:"isPinned"`
	IsRecent bool   `json:"isRecent"`
}

type Request struct {
	Action string `json:"action"`
	Folder string `json:"folder"`
	Pinned bool   `json:"pinned"`
}

func main() {
	http.HandleFunc("/", handleRequest)
	http.ListenAndServe(":8080", nil)
}

func handleRequest(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	var response struct {
		Success bool        `json:"success"`
		Data    interface{} `json:"data,omitempty"`
		Error   string      `json:"error,omitempty"`
	}

	defer func() {
		json.NewEncoder(w).Encode(response)
	}()

	switch r.Method {
	case http.MethodGet:
		action := r.URL.Query().Get("action")
		switch action {
		case "getFolders":
			folders, err := getFoldersList()
			if err != nil {
				response.Error = err.Error()
				w.WriteHeader(http.StatusBadRequest)
				return
			}

			pinned, _ := readListFile(pinnedFile)
			recent, _ := readListFile(recentFile)

			var result []FolderInfo
			for _, folder := range folders {
				info, err := os.Stat(filepath.Join(baseDir, folder))
				if err != nil {
					continue
				}

				result = append(result, FolderInfo{
					Name:     folder,
					Modified: info.ModTime().Unix(),
					IsPinned: contains(pinned, folder),
					IsRecent: contains(recent, folder),
				})
			}

			response.Success = true
			response.Data = result

		case "download":
			folder := r.URL.Query().Get("folder")
			if err := downloadFolder(w, folder); err != nil {
				response.Error = err.Error()
				w.WriteHeader(http.StatusBadRequest)
			}
			return

		default:
			response.Error = "unknown action"
			w.WriteHeader(http.StatusBadRequest)
		}

	case http.MethodPost:
		var req Request
		if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
			response.Error = err.Error()
			w.WriteHeader(http.StatusBadRequest)
			return
		}

		switch req.Action {
		case "togglePin":
			if err := togglePin(req.Folder, req.Pinned); err != nil {
				response.Error = err.Error()
				w.WriteHeader(http.StatusBadRequest)
				return
			}
			response.Success = true

		case "addRecent":
			if err := addRecent(req.Folder); err != nil {
				response.Error = err.Error()
				w.WriteHeader(http.StatusBadRequest)
				return
			}
			response.Success = true

		default:
			response.Error = "unknown action"
			w.WriteHeader(http.StatusBadRequest)
		}

	default:
		response.Error = "method not allowed"
		w.WriteHeader(http.StatusMethodNotAllowed)
	}
}

func getFoldersList() ([]string, error) {
	entries, err := os.ReadDir(baseDir)
	if err != nil {
		return nil, err
	}

	var folders []string
	for _, entry := range entries {
		if entry.IsDir() && !strings.HasPrefix(entry.Name(), ".") {
			folders = append(folders, entry.Name())
		}
	}

	return folders, nil
}

func readListFile(filename string) ([]string, error) {
	if _, err := os.Stat(filename); os.IsNotExist(err) {
		return []string{}, nil
	}

	content, err := os.ReadFile(filename)
	if err != nil {
		return nil, err
	}

	return strings.Split(strings.TrimSpace(string(content)), "\n"), nil
}

func writeListFile(filename string, items []string) error {
	if err := os.MkdirAll(dataDir, 0755); err != nil {
		return err
	}

	content := strings.Join(items, "\n")
	return os.WriteFile(filename, []byte(content), 0644)
}

func contains(list []string, item string) bool {
	for _, i := range list {
		if i == item {
			return true
		}
	}
	return false
}

func togglePin(folder string, unpin bool) error {
	pinned, err := readListFile(pinnedFile)
	if err != nil {
		return err
	}

	var newPinned []string
	if unpin {
		for _, f := range pinned {
			if f != folder {
				newPinned = append(newPinned, f)
			}
		}
	} else {
		newPinned = append([]string{folder}, pinned...)
		if len(newPinned) > maxListSize {
			newPinned = newPinned[:maxListSize]
		}
	}

	return writeListFile(pinnedFile, newPinned)
}

func addRecent(folder string) error {
	recent, err := readListFile(recentFile)
	if err != nil {
		return err
	}

	if contains(recent, folder) {
		return nil
	}

	newRecent := append([]string{folder}, recent...)
	if len(newRecent) > maxListSize {
		newRecent = newRecent[:maxListSize]
	}

	return writeListFile(recentFile, newRecent)
}

func downloadFolder(w http.ResponseWriter, folder string) error {
	folderPath := filepath.Join(baseDir, folder)
	if _, err := os.Stat(folderPath); os.IsNotExist(err) {
		return errors.New("folder does not exist")
	}

	zipPath := filepath.Join(dataDir, folder+".zip")
	if err := createZip(folderPath, zipPath); err != nil {
		return err
	}
	defer os.Remove(zipPath)

	w.Header().Set("Content-Type", "application/zip")
	w.Header().Set("Content-Disposition", "attachment; filename=\""+filepath.Base(zipPath)+"\"")
	http.ServeFile(w, nil, zipPath)
	return nil
}

func createZip(source, target string) error {
	zipfile, err := os.Create(target)
	if err != nil {
		return err
	}
	defer zipfile.Close()

	archive := zip.NewWriter(zipfile)
	defer archive.Close()

	return filepath.Walk(source, func(path string, info os.FileInfo, err error) error {
		if err != nil {
			return err
		}

		header, err := zip.FileInfoHeader(info)
		if err != nil {
			return err
		}

		header.Name, err = filepath.Rel(source, path)
		if err != nil {
			return err
		}

		if info.IsDir() {
			header.Name += "/"
		} else {
			header.Method = zip.Deflate
		}

		writer, err := archive.CreateHeader(header)
		if err != nil {
			return err
		}

		if !info.IsDir() {
			file, err := os.Open(path)
			if err != nil {
				return err
			}
			defer file.Close()
			_, err = io.Copy(writer, file)
		}
		return err
	})
}