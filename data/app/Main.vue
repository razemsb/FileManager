class PopupNotifier {
  constructor(options = {}) {
      this.settings = {
          theme: 'light',
          colors: {
              success: '#22c55e',
              error: '#ef4444',
              info: '#3b82f6',
              warning: '#facc15'
          },
          position: 'bottom-right',
          duration: 3000,
          maxNotifications: 5,
          showTime: true,
          showClose: true,
          autoClose: true,
          ...options
      };

      this.icons = {
          success: '<i class="fa-solid fa-circle-check"></i>',
          error: '<i class="fa-solid fa-circle-xmark"></i>',
          info: '<i class="fa-solid fa-circle-info"></i>',
          warning: '<i class="fa-solid fa-triangle-exclamation"></i>'
      };

      this.initTheme();
      this.initContainers();
  }

  initTheme() {
      document.body.classList.add(`popupx-theme-${this.settings.theme}`);
      
      const root = document.documentElement;
      root.style.setProperty('--popup-success', this.settings.colors.success);
      root.style.setProperty('--popup-error', this.settings.colors.error);
      root.style.setProperty('--popup-info', this.settings.colors.info);
      root.style.setProperty('--popup-warning', this.settings.colors.warning);
  }

  initContainers() {
      const positions = ['top-left', 'top-right', 'bottom-left', 'bottom-right'];
      positions.forEach(pos => {
          const container = document.createElement('div');
          container.id = `popup-container-${pos}`;
          container.className = `popup-container ${pos}`;
          document.body.appendChild(container);
      });
  }

  setTheme(theme) {
      this.settings.theme = theme;
      document.body.classList.remove('popupx-theme-light', 'popupx-theme-dark');
      document.body.classList.add(`popupx-theme-${theme}`);
  }

  setColors(colors) {
      this.settings.colors = {...this.settings.colors, ...colors};
      this.initTheme();
  }

  show({
      type = 'info',
      message = '',
      title = '',
      duration = this.settings.duration,
      position = this.settings.position,
      showTime = this.settings.showTime,
      showClose = this.settings.showClose,
      autoClose = this.settings.autoClose,
      color = null
  } = {}) {
      const container = document.getElementById(`popup-container-${position}`);
      if (!container) return;

      if (container.children.length >= this.settings.maxNotifications) {
          container.removeChild(container.children[0]);
      }

      const currentTime = this.getCurrentTime();
      const popupId = `popup-${Date.now()}`;
      const customColor = color || this.settings.colors[type];

      const popup = document.createElement('div');
      popup.id = popupId;
      popup.className = `popup popup-${type}`;
      popup.style.setProperty('--popup-color', customColor);
      popup.setAttribute('role', 'alert');
      popup.setAttribute('aria-live', 'assertive');

      popup.innerHTML = `
          <div class="popup-icon">${this.icons[type] || this.icons.info}</div>
          <div class="popup-content">
              ${title ? `<div class="popup-title">${title}</div>` : ''}
              <div class="popup-message">${message}</div>
              ${showTime ? `<div class="popup-time">${currentTime}</div>` : ''}
          </div>
          ${showClose ? `
              <button class="popup-close" aria-label="Закрыть уведомление">
                  <i class="fa-solid fa-xmark"></i>
              </button>
          ` : ''}
      `;

      container.appendChild(popup);
      setTimeout(() => popup.classList.add('show'), 10);

      const closePopup = () => {
          popup.classList.remove('show');
          popup.classList.add('hide');
          popup.addEventListener('transitionend', () => popup.remove());
      };

      let timeoutId;

      if (autoClose) {
          timeoutId = setTimeout(closePopup, duration);
      }

      const closeBtn = popup.querySelector('.popup-close');
      if (closeBtn) {
          closeBtn.addEventListener('click', () => {
              if (timeoutId) clearTimeout(timeoutId);
              closePopup();
          });
      }

      popup.addEventListener('mouseenter', () => {
          if (timeoutId) clearTimeout(timeoutId);
      });

      popup.addEventListener('mouseleave', () => {
          if (autoClose) {
              timeoutId = setTimeout(closePopup, duration);
          }
      });

      return {
          close: () => {
              if (timeoutId) clearTimeout(timeoutId);
              closePopup();
          },
          update: (newOptions) => {

          }
      };
  }

  getCurrentTime() {
      const now = new Date();
      return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  success(options) {
      return this.show({ ...options, type: 'success' });
  }

  error(options) {
      return this.show({ ...options, type: 'error' });
  }

  info(options) {
      return this.show({ ...options, type: 'info' });
  }

  warning(options) {
      return this.show({ ...options, type: 'warning' });
  }
}

const notifier = new PopupNotifier();

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
    const showDbButton = ref(true);
    const showFolderCreator = ref(false);
    const testFolderCount = ref(50);
    const testFolderPrefix = ref('test_folder');
    const ProjectStatus = ref('Productionn');

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
        { id: 'Aurora', label: 'Aurora'},
        { id: 'GithubDark', label: 'Github Dark'}
      ],
    });

    function toggleSidebar() { 
      sidebarCollapsed.value = !sidebarCollapsed.value; 
    }

    const flatThemes = computed(() => [
      ...themes.value.light,
      ...themes.value.dark,
      ...themes.value.custom,
    ]);

    const theme = ref('NeoTokyo');
    const selectedThemeSetting = ref(theme.value);

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
        if (screenWidth >= 2560) return 28;
        if (screenWidth >= 1920) return 24;
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
        notifier.error({
          title: 'Ошибка загрузки папок.',
          message: 'Ошибка см консоль.',
          autoClose: false,
          showClose: true
        });
        console.error(err);
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
        notifier.error({
          title: 'Ошибка закрепления.',
          message: 'Ошибка см консоль.',
          autoClose: false,
          showClose: true
        });
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
        notifier.error({
          title: 'Ошибка открытия папки.',
          message: 'Ошибка см консоль.',
          autoClose: false,
          showClose: true
        });
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

        const contentType = response.headers.get('content-type') || '';

        if (!response.ok) {
          let errorMessage = `Ошибка сервера: ${response.status}`;

          try {
            if (contentType.includes('application/json')) {
              const errorData = await response.json();
              errorMessage = errorData.error || JSON.stringify(errorData);
            } else {
              const errorText = await response.text();
              errorMessage = errorText;
            }

            if (
              errorMessage.toLowerCase().includes('ziparchive') ||
              errorMessage.toLowerCase().includes('zip extension') ||
              errorMessage.includes('ZipArchive error')
            ) {
              throw new Error('ZIP_ARCHIVE_ERROR');
            }
          } catch (e) {
            notifier.error({
              title: 'Ошибка скачивания.',
              message: 'Ошибка см консоль.',
              autoClose: false,
              showClose: true
            });
            console.error('Не удалось прочитать ошибку:', e);
          }

          throw new Error(errorMessage);
        }

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

        const blob = await response.blob();

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
    // replace existing createContextMenuDom / showContextMenuAt / hideContextMenu with this

function createContextMenuDom() {
  if (menuEl) return menuEl;

  // create menu container
  menuEl = document.createElement('div');
  menuEl.className = 'efm-context-menu';
  menuEl.setAttribute('role', 'menu');
  menuEl.setAttribute('aria-hidden', 'true');
  menuEl.tabIndex = -1; 

  // inner markup — no inline styles here
  menuEl.innerHTML = `
    <button type="button" class="ctx-item" data-action="open" role="menuitem" tabindex="-1">
      <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span class="ctx-label">Открыть</span>
    </button>
    <button type="button" class="ctx-item" data-action="pin" role="menuitem" tabindex="-1">
      <i class="bi bi-pin" aria-hidden="true"></i><span class="ctx-label">Закрепить</span>
    </button>
    <button type="button" class="ctx-item" data-action="download" role="menuitem" tabindex="-1">
      <i class="bi bi-cloud-arrow-down" aria-hidden="true"></i><span class="ctx-label">Скачать</span>
    </button>
    <div class="ctx-sep" role="separator"></div>
    <button type="button" class="ctx-item danger" data-action="delete" role="menuitem" tabindex="-1">
      <i class="bi bi-trash" aria-hidden="true"></i><span class="ctx-label">Удалить</span>
    </button>
  `;

  // handle clicks (event delegation)
  menuEl.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-action]');
    if (!btn) return;
    ev.stopPropagation();
    ev.preventDefault();
    const action = btn.getAttribute('data-action');
    const folder = ctxFolder.value;
    if (!folder) {
      hideContextMenu();
      return;
    }

    switch (action) {
      case 'open': ctxOpen(folder); break;
      case 'pin': ctxTogglePin(folder); break;
      case 'download': ctxDownload(folder); break;
      case 'delete': ctxDelete(folder); break;
      case 'rename': ctxRename(folder); break;
      case 'copy': ctxCopyLink(folder); break;
      default: break;
    }
  });

  // keyboard nav inside menu
  menuEl.addEventListener('keydown', (ev) => {
    const items = Array.from(menuEl.querySelectorAll('.ctx-item'));
    const idx = items.indexOf(document.activeElement);
    if (ev.key === 'ArrowDown') {
      ev.preventDefault();
      const next = items[(idx + 1) % items.length];
      next.focus();
    } else if (ev.key === 'ArrowUp') {
      ev.preventDefault();
      const prev = items[(idx - 1 + items.length) % items.length];
      prev.focus();
    } else if (ev.key === 'Home') {
      ev.preventDefault();
      items[0].focus();
    } else if (ev.key === 'End') {
      ev.preventDefault();
      items[items.length - 1].focus();
    } else if (ev.key === 'Escape') {
      ev.preventDefault();
      hideContextMenu();
    } else if (ev.key === 'Enter' || ev.key === ' ') {
      ev.preventDefault();
      document.activeElement?.click();
    }
  });

  document.body.appendChild(menuEl);
  return menuEl;
}

function showContextMenuAt(x, y, folder) {
  const el = createContextMenuDom();
  ctxFolder.value = folder;

  // update pin label/icon
  const pinBtn = el.querySelector('[data-action="pin"]');
  if (pinBtn) {
    const icon = pinBtn.querySelector('i');
    const label = pinBtn.querySelector('.ctx-label');
    if (folder && folder.isPinned) {
      if (icon) icon.className = 'bi bi-pin-fill';
      if (label) label.textContent = 'Открепить';
    } else {
      if (icon) icon.className = 'bi bi-pin';
      if (label) label.textContent = 'Закрепить';
    }
  }

  // set position (only here we set inline left/top — that's OK)
  el.style.left = x + 'px';
  el.style.top = y + 'px';
  el.setAttribute('aria-hidden', 'false');
  el.classList.add('visible');

  // ensure not going out of viewport
  const rect = el.getBoundingClientRect();
  const pad = 8;
  if (rect.right > window.innerWidth) {
    const newLeft = Math.max(pad, window.innerWidth - rect.width - pad);
    el.style.left = newLeft + 'px';
  }
  if (rect.bottom > window.innerHeight) {
    const newTop = Math.max(pad, window.innerHeight - rect.height - pad);
    el.style.top = newTop + 'px';
  }

  // attach outside listener and keyboard (deferred to avoid immediate close)
  setTimeout(() => {
    outsideListener = (ev) => {
      if (!el.contains(ev.target)) hideContextMenu();
    };
    document.addEventListener('pointerdown', outsideListener, { capture: true });

    scrollListener = () => hideContextMenu();
    window.addEventListener('scroll', scrollListener, { passive: true, capture: true });

    // single key listener for global keys (Escape handled by menu too)
    keyListener = (ev) => {
      if (ev.key === 'Escape') hideContextMenu();

      // support ContextMenu/Shift+F10 on focused folder card
      if ((ev.key === 'ContextMenu' || (ev.shiftKey && ev.key === 'F10')) && document.activeElement) {
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

    // focus first item for keyboard users
    const first = el.querySelector('.ctx-item');
    if (first) {
      // mark keyboard usage (for focus styling)
      el.setAttribute('data-keyboard', 'true');
      first.focus();
    }
  }, 0);
}

function hideContextMenu() {
  if (!menuEl) return;
  menuEl.classList.remove('visible');
  menuEl.setAttribute('aria-hidden', 'true');
  ctxFolder.value = null;

  try {
    if (outsideListener) document.removeEventListener('pointerdown', outsideListener, { capture: true });
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

  // small delay then hide to allow transition (optional)
  setTimeout(() => {
    if (menuEl && !menuEl.classList.contains('visible')) {
      menuEl.style.left = '';
      menuEl.style.top = '';
      // keep in DOM for reuse
    }
  }, 200);
}


    // -----------------------
    // Context actions routed from menu (use existing helpers)
    // -----------------------
    async function ctxTogglePin(folder) {
      await togglePin(folder);
      hideContextMenu();
    }

    const generateRandomFolders = async () => {
      try {
        const randomPrefix = 'folder_' + Math.random().toString(36).substring(2, 8);
        
        const response = await fetch('data/api/api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'createFolders',
            count: 5,
            prefix: randomPrefix
          })
        })
        
        const result = await response.json()
        if (result.success) {
          notifier.success({
            message: 'Папки успешно созданы!'
          });

          setTimeout(() => window.location.reload(), 750);
        } else {
          alert('Ошибка: ' + result.error)
        }
      } catch (error) {
        console.error('Ошибка:', error)
        alert('Ошибка при создании папок')
      }
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
      const saved = localStorage.getItem('showDbButton')
      if (saved !== null) {
        showDbButton.value = saved === 'true'
      }
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
    
    watch(showDbButton, (newValue) => {
      localStorage.setItem('showDbButton', newValue);
    });
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
      showDbButton,
      generateRandomFolders,
      ProjectStatus,
      toggleSidebar
    };
  },
}).mount('#app');
