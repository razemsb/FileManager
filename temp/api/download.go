package main

import (
	"archive/zip"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
)

func enableCORS(w *http.ResponseWriter) {
	(*w).Header().Set("Access-Control-Allow-Origin", "*")
	(*w).Header().Set("Access-Control-Allow-Methods", "GET")
}

func downloadFolderHandler(w http.ResponseWriter, r *http.Request) {
	enableCORS(&w)

	folderName := r.URL.Query().Get("folder")
	if folderName == "" {
		http.Error(w, `{"error": "Папка не указана"}`, http.StatusBadRequest)
		return
	}

	folderPath := filepath.Join(".", folderName)
	if _, err := os.Stat(folderPath); os.IsNotExist(err) {
		http.Error(w, `{"error": "Папка не существует"}`, http.StatusNotFound)
		return
	}

	zipFilename := folderName + ".zip"
	zipFile, err := os.Create(zipFilename)
	if err != nil {
		http.Error(w, `{"error": "Не удалось создать архив"}`, http.StatusInternalServerError)
		return
	}
	defer os.Remove(zipFilename)
	defer zipFile.Close()

	zipWriter := zip.NewWriter(zipFile)
	defer zipWriter.Close()

	err = filepath.Walk(folderPath, func(filePath string, info os.FileInfo, err error) error {
		if err != nil {
			return err
		}
		if info.IsDir() {
			return nil
		}

		file, err := os.Open(filePath)
		if err != nil {
			return err
		}
		defer file.Close()

		relPath, _ := filepath.Rel(folderPath, filePath)
		relPath = filepath.Join(folderName, relPath)
		relPath = filepath.ToSlash(relPath)

		zipEntry, err := zipWriter.Create(relPath)
		if err != nil {
			return err
		}

		_, err = io.Copy(zipEntry, file)
		return err
	})

	if err != nil {
		http.Error(w, `{"error": "Ошибка при создании архива"}`, http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/zip")
	w.Header().Set("Content-Disposition", fmt.Sprintf(`attachment; filename="%s"`, zipFilename))

	file, err := os.Open(zipFilename)
	if err != nil {
		http.Error(w, `{"error": "Ошибка чтения архива"}`, http.StatusInternalServerError)
		return
	}
	defer file.Close()

	io.Copy(w, file)
}

func main() {
	http.HandleFunc("/api/download", downloadFolderHandler)
	fmt.Println("Сервер запущен на http://localhost:8080")
	http.ListenAndServe(":8080", nil)
}