const { createApp, ref, computed, onMounted, nextTick, watch, onBeforeUnmount } = Vue;

createApp({
  setup() {
    // -----------------------
    // STATE
    // -----------------------
    const folders = ref([]);
    const searchQuery = ref('');
    const isLoading = ref(false);
    const activeFilter = ref('all');
    const currentPage = ref(1);
    const itemsPerPage = ref(12);
    const sidebarCollapsed = ref(false);
    const error = ref(null);
    const tooltipInstances = ref([]);
    const isSettingsOpen = ref(false);
    const activeTab = ref('general');
    const showDbButton = ref(false);

    const settings = ref({
      perPageMode: 'auto',
      perPageValue: 16,
    });

    const errorModal = ref({
      isOpen: false,
      title: '',
      message: '',
    });

    const categories = ref({});

    const themes = ref({
      light: [
        { id: 'japan', label: 'Japan' }
      ],
      dark: [
        { id: 'china', label: 'China' },
        { id: 'MidNightBlue', label: 'Midnight Blue' },
        { id: 'OldDark', label: 'Old Dark' },
      ],
      custom: [
        { id: 'NeoTokyo', label: 'Neo Tokyo' },
        { id: 'HelloKitty', label: 'Hello Kitty'}
      ],
    });

    const flatThemes = computed(() => [
      ...themes.value.light,
      ...themes.value.dark,
      ...themes.value.custom,
    ]);

    const theme = ref('japan');
    const selectedThemeSetting = ref(theme.value);

    // -----------------------
    // CONTEXT MENU (DOM-created fallback)
    // -----------------------
    let menuEl = null; // DOM node for menu (created on mount)
    let outsideListener = null;
    let keyListener = null;
    let scrollListener = null;
    // track currently opened folder object
    const ctxFolder = ref(null);

    // -----------------------
    // HELPERS
    // -----------------------
    function debounce(fn, wait = 160) {
      let timeout;
      return function () {
        const ctx = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(ctx, args), wait);
      };
    }

    function calculateOptimalItemsPerPage() {
      const screenWidth = window.innerWidth;
      const screenHeight = window.innerHeight;
      const aspectRatio = screenWidth / screenHeight;

      if (aspectRatio >= 1.7 && aspectRatio <= 1.8) {
        if (screenWidth >= 3840) return 32;
        if (screenWidth >= 2560) return 35;
        if (screenWidth >= 1920) return 16;
        if (screenWidth >= 1600) return 20;
        return 16;
      }

      if (aspectRatio > 2.0) {
        if (screenWidth >= 5120) return 40;
        if (screenWidth >= 3440) return 36;
        return 28;
      }

      if (aspectRatio > 1.8) return 16;
      return 12;
    }

    // -----------------------
    // TOOLTIP helpers (Bootstrap)
    // -----------------------
    const initTooltips = () => {
      try {
        destroyTooltips();
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipInstances.value = Array.from(tooltipElements).map(
          (el) => new bootstrap.Tooltip(el, { placement: 'bottom', trigger: 'hover' })
        );
      } catch (err) {
        // silently ignore if bootstrap not present
      }
    };

    const destroyTooltips = () => {
      tooltipInstances.value.forEach((t) => {
        try {
          t.dispose();
        } catch (e) {
          /* noop */
        }
      });
      tooltipInstances.value = [];
    };

    const updateTooltips = () => {
      nextTick(() => {
        destroyTooltips();
        initTooltips();
      });
    };

    // -----------------------
    // API / folders
    // -----------------------

    const loadFolders = async () => {
      try {
        isLoading.value = true;
        const response = await axios.get('data/api/api.php?action=getFolders');
        if (response.data?.success) {
          folders.value = response.data.data || [];
          await nextTick();
          initTooltips();
        } else {
          throw new Error(response.data?.error || 'Ошибка загрузки данных');
        }
      } catch (err) {
        error.value = err.message || String(err);
        folders.value = [];
        console.error('Ошибка загрузки папок:', err);
      } finally {
        isLoading.value = false;
      }
    };

    const togglePin = async (folder) => {
      if (!folder) return;
      try {
        isLoading.value = true;
        const response = await axios.post('data/api/api.php', {
          action: 'togglePin',
          folder: folder.name,
          pinned: folder.isPinned,
        });
        if (!response.data?.success)
          throw new Error(response.data?.error || 'Ошибка при изменении статуса');
        await loadFolders();
      } catch (err) {
        console.error('togglePin error:', err);
      } finally {
        isLoading.value = false;
      }
    };

    const openFolder = async (folder) => {
      if (!folder) return;
      try {
        await axios.post('data/api/api.php', { action: 'addRecent', folder: folder.name });
        window.location.href = folder.name;
      } catch (err) {
        console.error('openFolder error:', err);
      }
    };

    const downloadFolder = async (folder) => {
      if (!folder) return;
      try {
        isLoading.value = true;
        const response = await fetch(
          `data/api/api.php?action=download&folder=${encodeURIComponent(folder.name)}`
        );

        // Сначала проверяем Content-Type
        const contentType = response.headers.get('content-type') || '';

        if (!response.ok) {
          let errorMessage = `Ошибка сервера: ${response.status}`;

          // Пытаемся прочитать ответ как JSON или текст
          try {
            if (contentType.includes('application/json')) {
              const errorData = await response.json();
              errorMessage = errorData.error || JSON.stringify(errorData);
            } else {
              const errorText = await response.text();
              errorMessage = errorText;
            }

            // Проверяем на наличие ошибок ZipArchive
            if (
              errorMessage.toLowerCase().includes('ziparchive') ||
              errorMessage.toLowerCase().includes('zip extension') ||
              errorMessage.includes('ZipArchive error')
            ) {
              throw new Error('ZIP_ARCHIVE_ERROR');
            }
          } catch (e) {
            // Не удалось прочитать ошибку
            console.error('Не удалось прочитать ошибку:', e);
          }

          throw new Error(errorMessage);
        }

        // Если ответ успешный, но Content-Type JSON - это может быть ошибка
        if (contentType.includes('application/json')) {
          const responseData = await response.json();

          if (
            responseData.error &&
            (responseData.error.toLowerCase().includes('ziparchive') ||
              responseData.error.toLowerCase().includes('zip extension') ||
              responseData.error.toLowerCase().includes('exception') ||
              responseData.error.toLowerCase().includes('error'))
          ) {
            throw new Error('ZIP_ARCHIVE_ERROR');
          }

          // Если это JSON но не ошибка, продолжаем нормальную обработку
          const blob = new Blob([JSON.stringify(responseData)], { type: 'application/json' });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = `${folder.name}.json`;
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
          document.body.removeChild(a);
          return;
        }

        // Обычная обработка бинарных данных
        const blob = await response.blob();

        // Дополнительная проверка: если blob очень маленький, это может быть ошибка
        if (blob.size < 100) {
          const text = await blob.text();
          if (
            text.toLowerCase().includes('ziparchive') ||
            text.toLowerCase().includes('error') ||
            text.toLowerCase().includes('exception')
          ) {
            throw new Error('ZIP_ARCHIVE_ERROR');
          }
        }

        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${folder.name}.zip`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
      } catch (err) {
        console.error('Ошибка при скачивании:', err);

        if (err.message === 'ZIP_ARCHIVE_ERROR') {
          showErrorModal(
            'Ошибка создания архива',
            'Системная ошибка: отсутствует поддержка ZIP на сервере.\n\n' +
              'Для работы функции архивирования необходимо:\n' +
              '1. Установить PHP расширение ZipArchive\n' +
              '2. Включить extension=zip в php.ini\n\n' +
              'Обратитесь к системному администратору.'
          );
        } else if (err.message.includes('Ошибка сервера: 500')) {
          showErrorModal(
            'Внутренняя ошибка сервера',
            'Произошла внутренняя ошибка сервера. Возможно, отсутствует необходимое расширение PHP.'
          );
        } else {
          showErrorModal(
            'Ошибка загрузки',
            err.message || 'Неизвестная ошибка при скачивании папки.'
          );
        }
      } finally {
        isLoading.value = false;
      }
    };

    const showErrorModal = (title, message) => {
      errorModal.value = {
        isOpen: true,
        title: title,
        message: message,
      };
    };

    // Функция для закрытия модалки
    const closeErrorModal = () => {
      errorModal.value.isOpen = false;
    };

    // Функция для копирования информации об ошибке
    const copyErrorToClipboard = async () => {
      const errorText = `Ошибка ZipArchive: ${errorModal.value.title}\n\n${errorModal.value.message}`;

      try {
        await navigator.clipboard.writeText(errorText);
        // Можно показать уведомление об успешном копировании
        alert('Информация об ошибке скопирована в буфер обмена');
      } catch (err) {
        console.error('Не удалось скопировать текст:', err);
      }
    };

    // -----------------------
    // FILTERS / PAGINATION
    // -----------------------
    const filteredFolders = computed(() => {
      let result = (folders.value || []).slice();

      if (activeFilter.value === 'pinned') result = result.filter((f) => f.isPinned);
      else if (activeFilter.value === 'recent') result = result.filter((f) => f.isRecent);

      if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter((f) => (f.name || '').toLowerCase().includes(q));
      }

      return result;
    });

    const totalPages = computed(() =>
      Math.max(1, Math.ceil(filteredFolders.value.length / itemsPerPage.value))
    );

    const paginatedFolders = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value;
      return filteredFolders.value.slice(start, start + itemsPerPage.value);
    });

    const visiblePages = computed(() => {
      const pages = [];
      const range = 2;
      for (let i = 1; i <= totalPages.value; i++) {
        if (
          i === 1 ||
          i === totalPages.value ||
          (i >= currentPage.value - range && i <= currentPage.value + range)
        ) {
          pages.push(i);
        } else if (i === currentPage.value - range - 1 || i === currentPage.value + range + 1) {
          pages.push('...');
        }
      }
      return pages;
    });

    const totalCount = computed(() => (folders.value || []).length);
    const pinnedCount = computed(() => (folders.value || []).filter((f) => f.isPinned).length);
    const recentCount = computed(() => (folders.value || []).filter((f) => f.isRecent).length);

    // -----------------------
    // SETTINGS / helpers (minimal)
    // -----------------------
    const resolvedItemsPerPage = computed(() =>
      settings.value.perPageMode === 'auto'
        ? calculateOptimalItemsPerPage()
        : settings.value.perPageValue
    );

    function ensureThemeLink() {
      let link = document.getElementById('theme-link');
      if (!link) {
        link = document.createElement('link');
        link.id = 'theme-link';
        link.rel = 'stylesheet';
        document.head.appendChild(link);
      }
      return link;
    }

    function applyTheme(themeId) {
      if (!themeId) return;
      if (!flatThemes.value.some((t) => t.id === themeId)) return;
      ensureThemeLink().href = `data/css/themes/${themeId}.css`;
      theme.value = themeId;
      localStorage.setItem('efm_theme', themeId);
      try {
        document.documentElement.setAttribute('data-efm-theme', themeId);
      } catch (e) {}
    }

    // -----------------------
    // SEARCH debounce (template uses filterFolders)
    // -----------------------
    const filterFolders = debounce(() => {
      currentPage.value = 1;
      updateTooltips();
    }, 160);

    // -----------------------
    // CONTEXT MENU: create DOM menu (robust)
    // -----------------------
    function createContextMenuDom() {
      // If already exists, return
      if (menuEl) return menuEl;

      menuEl = document.createElement('div');
      menuEl.className = 'context-menu';
      // minimal inline styles to ensure visibility; real CSS should style it
      menuEl.style.position = 'fixed';
      menuEl.style.zIndex = '99999';
      menuEl.style.minWidth = '200px';
      menuEl.style.display = 'none';
      menuEl.style.background = 'var(--menu-bg, #fff)';
      menuEl.style.boxShadow = '0 6px 20px rgba(0,0,0,0.12)';
      menuEl.style.borderRadius = '8px';
      menuEl.style.padding = '6px';
      menuEl.style.fontSize = '14px';
      menuEl.style.color = 'var(--menu-color, #111)';
      menuEl.style.userSelect = 'none';
      menuEl.style.outline = 'none';

      // build inner HTML
      menuEl.innerHTML = `
        <button type="button" class="ctx-item ctx-open" data-action="open" style="width:100%;text-align:left;padding:8px;border:none;background:transparent;cursor:pointer;">
          <i class="bi bi-box-arrow-up-right"></i><span>Открыть</span>
        </button>
        <button type="button" class="ctx-item ctx-pin" data-action="pin" style="width:100%;text-align:left;padding:8px;border:none;background:transparent;cursor:pointer;">
          <i class="bi bi-pin"></i><span>Закрепить</span>
        </button>
        <button type="button" class="ctx-item ctx-download" data-action="download" style="width:100%;text-align:left;padding:8px;border:none;background:transparent;cursor:pointer;">
          <i class="bi bi-cloud-arrow-down"></i><span>Скачать</span>
        </button>
        <hr style="margin:6px 0;border:none;border-top:1px solid rgba(0,0,0,0.06)">
        <button type="button" class="ctx-item ctx-delete" data-action="delete" style="width:100%;text-align:left;padding:8px;border:none;background:transparent;cursor:pointer;">
          <i class="bi bi-trash"></i><span>Удалить</span>
        </button>
      `;

      // attach item listeners (use event delegation)
      menuEl.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.ctx-item');
        if (!btn) return;
        ev.stopPropagation();
        ev.preventDefault();
        const action = btn.getAttribute('data-action');
        const folder = ctxFolder.value;
        if (!folder) {
          hideContextMenu();
          return;
        }

        // route actions
        switch (action) {
          case 'open':
            ctxOpen(folder);
            break;
          case 'pin':
            ctxTogglePin(folder);
            break;
          case 'rename':
            ctxRename(folder);
            break;
          case 'copy':
            ctxCopyLink(folder);
            break;
          case 'download':
            ctxDownload(folder);
            break;
          case 'delete':
            ctxDelete(folder);
            break;
          default:
            break;
        }
      });

      document.body.appendChild(menuEl);
      return menuEl;
    }

    function showContextMenuAt(x, y, folder) {
      // create if missing
      const el = createContextMenuDom();
      ctxFolder.value = folder;
      // update pin label/icon depending on folder.isPinned
      const pinBtn = el.querySelector('.ctx-pin');
      if (folder && folder.isPinned) {
        pinBtn.innerHTML = `<i class="bi bi-pin-fill" style="margin-right:8px"></i><span>Открепить</span>`;
      } else {
        pinBtn.innerHTML = `<i class="bi bi-pin" style="margin-right:8px"></i><span>Закрепить</span>`;
      }

      el.style.display = 'block';
      el.style.left = x + 'px';
      el.style.top = y + 'px';

      // adjust if out of viewport
      const rect = el.getBoundingClientRect();
      const pad = 8;
      if (rect.right > window.innerWidth) {
        el.style.left = Math.max(pad, window.innerWidth - rect.width - pad) + 'px';
      }
      if (rect.bottom > window.innerHeight) {
        el.style.top = Math.max(pad, window.innerHeight - rect.height - pad) + 'px';
      }

      // attach outside listener and keyboard (deferred to avoid immediate close)
      setTimeout(() => {
        outsideListener = (ev) => {
          if (!el.contains(ev.target)) hideContextMenu();
        };
        document.addEventListener('pointerdown', outsideListener, { capture: true });

        scrollListener = () => hideContextMenu();
        window.addEventListener('scroll', scrollListener, { passive: true, capture: true });

        keyListener = (ev) => {
          if (ev.key === 'Escape') hideContextMenu();
          // support opening via keyboard for focused folder card (ContextMenu or Shift+F10)
          if (
            (ev.key === 'ContextMenu' || (ev.shiftKey && ev.key === 'F10')) &&
            document.activeElement
          ) {
            const elFocus = document.activeElement;
            const folderName = elFocus.getAttribute?.('data-folder-name');
            if (folderName) {
              const f = folders.value.find((ff) => ff.name === folderName);
              if (f) {
                const rect2 = elFocus.getBoundingClientRect();
                showContextMenuAt(rect2.left + 8, rect2.bottom - 8, f);
              }
            }
          }
        };
        window.addEventListener('keydown', keyListener);
      }, 0);
    }

    function hideContextMenu() {
      if (!menuEl) return;
      menuEl.style.display = 'none';
      ctxFolder.value = null;
      try {
        if (outsideListener)
          document.removeEventListener('pointerdown', outsideListener, { capture: true });
      } catch (e) {}
      outsideListener = null;
      try {
        if (scrollListener) window.removeEventListener('scroll', scrollListener, { capture: true });
      } catch (e) {}
      scrollListener = null;
      try {
        if (keyListener) window.removeEventListener('keydown', keyListener);
      } catch (e) {}
      keyListener = null;
    }

    // -----------------------
    // Context actions routed from menu (use existing helpers)
    // -----------------------
    async function ctxTogglePin(folder) {
      await togglePin(folder);
      hideContextMenu();
    }

    function ctxOpen(folder) {
      openFolder(folder);
      hideContextMenu();
    }

    async function ctxDownload(folder) {
      await downloadFolder(folder);
      hideContextMenu();
    }

    async function ctxRename(folder) {
      if (!folder) return hideContextMenu();
      const newName = prompt('Новое имя папки', folder.name);
      if (!newName || !newName.trim()) return hideContextMenu();
      try {
        isLoading.value = true;
        const resp = await axios.post('data/api/api.php', {
          action: 'renameFolder',
          folder: folder.name,
          newName: newName.trim(),
        });
        if (!resp.data?.success) throw new Error(resp.data?.error || 'Ошибка при переименовании');
        await loadFolders();
      } catch (e) {
        console.error('rename error', e);
        alert('Не удалось переименовать — см. консоль');
      } finally {
        isLoading.value = false;
        hideContextMenu();
      }
    }

    async function ctxCopyLink(folder) {
      if (!folder) return hideContextMenu();
      const link = location.origin + '/' + encodeURIComponent(folder.name);
      try {
        await navigator.clipboard.writeText(link);
      } catch (e) {
        console.error('clipboard error', e);
        alert('Не удалось скопировать ссылку');
      } finally {
        hideContextMenu();
      }
    }

    async function ctxDelete(folder) {
      if (!folder) return hideContextMenu();
      if (!confirm(`Удалить папку «${folder.name}»?`)) return hideContextMenu();
      try {
        isLoading.value = true;
        const resp = await axios.post('data/api/api.php', {
          action: 'deleteFolder',
          folder: folder.name,
        });
        if (!resp.data?.success) throw new Error(resp.data?.error || 'Удаление не удалось');
        await loadFolders();
      } catch (e) {
        console.error('delete error', e);
        const idx = folders.value.findIndex((f) => f.name === folder.name);
        if (idx !== -1) folders.value.splice(idx, 1);
      } finally {
        isLoading.value = false;
        hideContextMenu();
      }
    }

    // -----------------------
    // Template binding: handler to call on right-click on folder
    // Expectation: in HTML you have @contextmenu.prevent="onRightClickFolder($event, folder)" or similar
    // -----------------------
    function onRightClickFolder(event, folder) {
      event.preventDefault();
      if (isSettingsOpen.value) return; // don't open if settings modal active
      // Prefer client coords; fallback to mouse event pageX/pageY
      const x =
        event.clientX ||
        event.pageX ||
        (event.touches && event.touches[0] && event.touches[0].clientX) ||
        0;
      const y =
        event.clientY ||
        event.pageY ||
        (event.touches && event.touches[0] && event.touches[0].clientY) ||
        0;
      showContextMenuAt(x, y, folder);
    }

    // -----------------------
    // Misc UI helpers
    // -----------------------
    const scrollToTop = () => window.scrollTo({ top: 0, behavior: 'smooth' });

    const formatDate = (timestamp) => {
      if (timestamp === undefined || timestamp === null) return '-';
      try {
        const ts = String(timestamp).length > 12 ? timestamp : timestamp * 1000;
        return new Date(ts).toLocaleDateString('ru-RU', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        });
      } catch (e) {
        return '-';
      }
    };

    const formatFolderName = (name) => {
      if (!name) return '';
      if (name.length <= 8) return name;
      return name.slice(0, 8) + '...';
    };

    const setFilter = (filter) => {
      activeFilter.value = filter;
      currentPage.value = 1;
      updateTooltips();
    };

    // -----------------------
    // LIFECYCLE
    // -----------------------
    const onResizeHandler = debounce(() => {
      if (settings.value.perPageMode === 'auto') {
        itemsPerPage.value = calculateOptimalItemsPerPage();
        currentPage.value = 1;
        updateTooltips();
      }
    }, 200);

    function onEsc(e) {
      if (e.key === 'Escape' && isSettingsOpen.value) closeSettings();
    }

    function openSettings() {
      selectedThemeSetting.value = theme.value;
      isSettingsOpen.value = true;
      nextTick(() => {
        document.documentElement.style.overflow = 'hidden';
      });
    }

    function closeSettings() {
      isSettingsOpen.value = false;
      document.documentElement.style.overflow = '';
    }

    function applySettings() {
      itemsPerPage.value = resolvedItemsPerPage.value;
      currentPage.value = 1;
      localStorage.setItem('efm_settings', JSON.stringify(settings.value));
      if (selectedThemeSetting.value && selectedThemeSetting.value !== theme.value)
        applyTheme(selectedThemeSetting.value);
      closeSettings();
    }

    onMounted(async () => {
      // restore settings
      try {
        const raw = localStorage.getItem('efm_settings');
        if (raw) {
          const parsed = JSON.parse(raw);
          if (parsed && (parsed.perPageMode === 'auto' || parsed.perPageMode === 'manual')) {
            settings.value.perPageMode = parsed.perPageMode;
          }
          if (parsed && Number.isFinite(parsed.perPageValue)) {
            settings.value.perPageValue = parsed.perPageValue;
          }
        }
      } catch (_) {
        /* ignore */
      }

      // theme init
      try {
        const saved = localStorage.getItem('efm_theme');
        const systemPrefersDark =
          window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (saved && flatThemes.value.some((t) => t.id === saved)) {
          applyTheme(saved);
        } else {
          applyTheme(systemPrefersDark ? 'china' : 'japan');
        }
      } catch (e) {
        /* noop */
      }

      itemsPerPage.value = resolvedItemsPerPage.value;
      await loadFolders();
      initTooltips();

      window.addEventListener('resize', onResizeHandler);
      const checkScreenSize = () => {
        sidebarCollapsed.value = window.innerWidth < 992;
      };
      checkScreenSize();
      window.addEventListener('resize', checkScreenSize);

      window.addEventListener('keydown', onEsc);

      // create menu DOM proactively so it's ready
      createContextMenuDom();
    });

    onBeforeUnmount(() => {
      window.removeEventListener('resize', onResizeHandler);
      window.removeEventListener('resize', () => {
        /* noop */
      });
      window.removeEventListener('keydown', onEsc);
      destroyTooltips();
      // remove created menu and listeners
      try {
        if (menuEl) menuEl.remove();
      } catch (e) {}
      try {
        if (outsideListener)
          document.removeEventListener('pointerdown', outsideListener, { capture: true });
      } catch (e) {}
      try {
        if (scrollListener) window.removeEventListener('scroll', scrollListener, { capture: true });
      } catch (e) {}
      try {
        if (keyListener) window.removeEventListener('keydown', keyListener);
      } catch (e) {}
    });

    watch(
      () => settings.value.perPageMode,
      (v) => {
        itemsPerPage.value =
          v === 'auto' ? calculateOptimalItemsPerPage() : settings.value.perPageValue;
      }
    );
    // -----------------------
    // RETURN (to template)
    // -----------------------
    return {
      folders,
      searchQuery,
      isLoading,
      activeFilter,
      currentPage,
      itemsPerPage,
      sidebarCollapsed,
      error,
      filteredFolders,
      totalPages,
      paginatedFolders,
      visiblePages,
      totalCount,
      pinnedCount,
      recentCount,
      isSettingsOpen,
      activeTab,
      settings,
      resolvedItemsPerPage,
      themes,
      theme,
      selectedThemeSetting,
      applyTheme,
      showErrorModal,
      closeErrorModal,
      copyErrorToClipboard,
      errorModal,
      loadFolders,
      scrollToTop,
      setFilter,
      togglePin,
      openFolder,
      downloadFolder,
      nextPage: () => {
        if (currentPage.value < totalPages.value) {
          currentPage.value++;
          scrollToTop();
          updateTooltips();
        }
      },
      prevPage: () => {
        if (currentPage.value > 1) {
          currentPage.value--;
          scrollToTop();
          updateTooltips();
        }
      },
      goToPage: (p) => {
        if (p !== '...') {
          currentPage.value = p;
          scrollToTop();
          updateTooltips();
        }
      },
      formatDate,
      formatFolderName,
      openSettings,
      closeSettings,
      applySettings,
      onRightClickFolder,
      filterFolders,
      showDbButton
    };
  },
}).mount('#app');
