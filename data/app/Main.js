const { createApp, ref, computed, onMounted, nextTick, watch, onBeforeUnmount } = Vue;
createApp({
  setup() {
    const notifier = new window.PopupNotifier({ theme: 'dark' });
    const folders = ref([]);
    const searchQuery = ref('');
    const isLoading = ref(false);
    const activeFilter = ref('all');
    const currentPage = ref(1);
    const itemsPerPage = ref(12);
    const error = ref(null);
    const tooltipInstances = ref([]);
    const isSettingsOpen = ref(false);
    const activeTab = ref('general');
    const showDbButton = ref(true);
    const showFolderCreator = ref(false);
    const testFolderCount = ref(50);
    const testFolderPrefix = ref('test_folder');
    const ProjectStatus = ref('Production');
    const ornamentPatterns = ref([
      'toy-1',
      'toy-2',
      'toy-3'
    ]);
    // Development / Production
    const savedRaw = localStorage.getItem('efm_settings');
    const savedParsed = savedRaw ? JSON.parse(savedRaw) : {};
    const savedSettings = JSON.parse(localStorage.getItem('efm_settings') || '{}');

    const settings = ref({
      perPageMode: savedSettings.perPageMode || 'auto',
      perPageValue: savedSettings.perPageValue || 20,
      snowEnabled: savedSettings.snowEnabled !== undefined ? savedSettings.snowEnabled : true,
      sidebarCollapsed: savedSettings.sidebarCollapsed !== undefined ? savedSettings.sidebarCollapsed : false,
    });

    const saveSettings = () => {
      localStorage.setItem('efm_settings', JSON.stringify(settings.value));
    };

    const toggleSidebar = () => {
      settings.value.sidebarCollapsed = !settings.value.sidebarCollapsed;
      saveSettings();
      setTimeout(() => {
        if (typeof initTooltips === 'function') initTooltips();
      }, 350);
    };

    const emptyStateContext = computed(() => {
      if (searchQuery.value) {
        return {
          type: 'search',
          icon: 'bi bi-search',
          subIcon: 'bi bi-question-lg',
          title: 'Ничего не найдено',
          description: `По запросу "${searchQuery.value}" совпадений нет.`,
          showAction: false
        };
      }
      if (activeFilter.value === 'pinned') {
        return {
          type: 'pinned',
          icon: 'bi bi-pin-angle',
          subIcon: 'bi bi-slash-circle',
          title: 'Нет закрепленных папок',
          description: 'Нажмите на иконку булавки у папки, чтобы закрепить её здесь.',
          showAction: false
        };
      }
      if (activeFilter.value === 'recent') {
        return {
          type: 'recent',
          icon: 'bi bi-clock-history',
          subIcon: 'bi bi-hourglass',
          title: 'История пуста',
          description: 'Вы еще не открывали ни одной папки.',
          showAction: false
        };
      }
      if (activeFilter.value === 'categories' && selectedCategoryId.value) {
        return {
          type: 'category',
          icon: 'bi bi-tags',
          subIcon: 'bi bi-folder-x',
          title: 'Категория пуста',
          description: 'В этой категории пока нет папок.',
          showAction: false
        };
      }
      return {
        type: 'default',
        icon: 'bi bi-folder2-open',
        subIcon: 'bi bi-plus-lg',
        title: 'Папок пока нет',
        description: 'Создайте первую папку, чтобы начать работу!',
        showAction: true
      };
    });

    const categories = ref([]);
    const isCategoryModalOpen = ref(false);
    const categoryModalTargetFolder = ref(null);
    const newCategoryName = ref('');
    const showCategoriesPanel = ref(false);
    const showCategoryChips = ref(true);
    const initialVisibleCategories = 5;
    const showAllCategories = ref(false);
    const selectedCategoryId = ref(null);
    const isCreateFolderModalOpen = ref(false);
    const newFolderName = ref('');
    const folderNameInput = ref(null);
    const currentFolder = ref('');
    const snowflakeCount = ref(20); // колличество снежинок
    const fallSpeed = ref(7);     // скорость (чем меньше тем быстрее)
    const snowflakes = ref([]);

    const snowflakeTypes = ['❄', '❅', '❆'];

    const initSnow = () => {
      if (!settings.value.snowEnabled) {
        snowflakes.value = [];
        return;
      }

      const temp = [];

      for (let i = 0; i < snowflakeCount.value; i++) {
        temp.push({
          id: i,
          type: snowflakeTypes[Math.floor(Math.random() * snowflakeTypes.length)],
          left: Math.random() * 100 + '%',
          delay: Math.random() * 8 + 's',
          duration: (Math.random() * 4 + fallSpeed.value) + 's',
          opacity: Math.random() * 0.6 + 0.2,
          size: Math.random() * 1.2 + 0.6 + 'rem'
        });
      }
      snowflakes.value = temp;
    };

    function getRandomOrnamentPattern(index) {
      if (theme.value !== 'NewYear') return '';

      const patternIndex = (index * 13 + 7) % ornamentPatterns.value.length;
      return ornamentPatterns.value[patternIndex];
    }

    const savedSnowStatus = localStorage.getItem('snow_enabled');
    const toggleSnow = () => {
      settings.value.snowEnabled = !settings.value.snowEnabled;

      localStorage.setItem('snow_enabled', settings.value.snowEnabled);

      initSnow();

      const statusText = settings.value.snowEnabled ? 'включен' : 'выключен';
      notifier.info({ message: `Новогодний вайб ${statusText}` });
    };

    onMounted(() => {
      initSnow();
    });

    const deleteConfirmAlert = ref({
      isOpen: false,
      folder: null,
      resolve: null,
    });

    const errorModal = ref({
      isOpen: false,
      title: '',
      message: '',
    });

    const themes = ref({
      light: [{ id: 'japan', label: 'Japan' }],
      dark: [
        { id: 'china', label: 'China' },
        { id: 'MidNightBlue', label: 'Midnight Blue' },
        { id: 'OldDark', label: 'Old Dark' },
      ],
      custom: [
        { id: 'NeoTokyo', label: 'Neo Tokyo' },
        { id: 'Anarchy', label: 'Anarchy' },
        { id: 'NewYear', label: '🎅New Year' },
      ],
    });

    const cssFile = computed(() => {
      return ProjectStatus.value === 'Development'
        ? 'data/css/style.css'
        : 'data/css/style.min.css';
    });

    onMounted(() => {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = cssFile.value;
      link.id = 'main-css';
      document.head.appendChild(link);
    });

    watch(() => ProjectStatus.value, (newStatus) => {
      const existingLink = document.getElementById('main-css');
      if (existingLink) {
        existingLink.href = newStatus === 'Development'
          ? 'data/css/style.css'
          : 'data/css/style.min.css';
      }
    });

    const flatThemes = computed(() => [
      ...themes.value.light,
      ...themes.value.dark,
      ...themes.value.custom,
    ]);

    const theme = ref('NeoTokyo');
    const selectedThemeSetting = ref(theme.value);

    let menuEl = null;
    let outsideListener = null;
    let keyListener = null;
    let scrollListener = null;

    const ctxFolder = ref(null);

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

    const initTooltips = () => {
      try {
        destroyTooltips();
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipInstances.value = Array.from(tooltipElements).map(
          (el) => new bootstrap.Tooltip(el, { placement: 'bottom', trigger: 'hover' })
        );
      } catch (err) { }
    };

    function selectCategory(catId) {
      selectedCategoryId.value = catId;
      activeFilter.value = 'categories';
      currentPage.value = 1;
    }

    function toggleShowAllCategories() {
      showAllCategories.value = !showAllCategories.value;
    }

    const visibleCategories = computed(() => {
      if (showAllCategories.value) return categories.value;
      return categories.value.slice(0, initialVisibleCategories);
    });

    const filteredFolders = computed(() => {
      let result = folders.value.slice();

      if (activeFilter.value === 'pinned') {
        result = result.filter((f) => f.is_pinned);
      } else if (activeFilter.value === 'recent') {
        result = result.filter((f) => f.is_recent);
      } else if (activeFilter.value === 'categories' && selectedCategoryId.value) {
        const cat = categories.value.find((c) => c.id === selectedCategoryId.value);
        if (cat && Array.isArray(cat.folders)) {
          result = result.filter((f) => cat.folders.includes(f.name));
        } else {
          result = [];
        }
      }


      if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter((f) => f.name.toLowerCase().includes(q));
      }

      return result;
    });

    const destroyTooltips = () => {
      tooltipInstances.value.forEach((t) => {
        try {
          t.dispose();
        } catch (e) { }
      });
      tooltipInstances.value = [];
    };

    const updateTooltips = () => {
      nextTick(() => {
        destroyTooltips();
        initTooltips();
      });
    };

    const loadCategories = async () => {
      try {
        const response = await axios.get('data/api/categories');

        const fetchedData = response.data.data;

        if (Array.isArray(fetchedData)) {
          categories.value = fetchedData;
        } else {
          console.error('Ожидался массив, пришло:', fetchedData);
          categories.value = [];
        }
      } catch (err) {
        console.error('categories load failed', err.response?.data || err);
        notifier.error({ message: 'Ошибка загрузки категорий' });
      }
    };

    function openCreateFolderModal() {
      isCreateFolderModalOpen.value = true;
      newFolderName.value = '';
      nextTick(() => {
        if (folderNameInput.value) {
          folderNameInput.value.focus();
        }
      });
    }

    function closeCreateFolderModal() {
      isCreateFolderModalOpen.value = false;
      newFolderName.value = '';
    }

    async function createFolder() {
      const name = (newFolderName.value || '').trim();
      if (!name) {
        notifier.info({ message: 'Введите название папки' });
        return;
      }

      try {
        isLoading.value = true;
        const resp = await axios.post('data/api/folders', {
          name: name
        });

        notifier.success({ message: 'Папка успешно создана' });
        closeCreateFolderModal();
        await loadFolders();

      } catch (e) {
        console.error(e);
        const errorMsg = e.response?.data?.error || e.message || 'Ошибка при создании папки';
        notifier.error({ message: errorMsg });
      } finally {
        isLoading.value = false;
      }
    }

    function openCategoryModalForFolder(folder = null) {
      categoryModalTargetFolder.value = folder || null;
      isCategoryModalOpen.value = true;
      newCategoryName.value = '';
    }

    function closeCategoryModal() {
      isCategoryModalOpen.value = false;
      categoryModalTargetFolder.value = null;
      newCategoryName.value = '';
    }

    async function createCategory() {
      const name = (newCategoryName.value || '').trim();
      if (!name) return;
      try {
        const resp = await axios.post('data/api/categories', { name });
        await loadCategories();
        newCategoryName.value = '';
        notifier.success({ message: 'Категория создана' });
      } catch (e) {
        notifier.error({ message: e.response?.data?.error || 'Ошибка' });
      }
    }

    async function addFolderToCategory(categoryId, folderName) {
      try {
        await axios.post(`data/api/categories/${categoryId}/folders`, { folderName });
        hideContextMenu();
        notifier.success({ message: 'Папка добавлена' });
        await loadCategories();
        closeCategoryModal();
      } catch (e) {
        notifier.error({ message: 'Ошибка добавления' });
      }
    }

    async function removeFolderFromCategory(categoryId, folderName) {
      if (!categoryId || !folderName) return;
      try {
        isLoading.value = true;
        await axios.delete(`data/api/categories/${categoryId}/folders/${encodeURIComponent(folderName)}`);

        notifier.success({ message: 'Папка убрана из категории' });

        await loadCategories();

        closeCategoryModal();
        hideContextMenu();
      } catch (e) {
        console.error(e);
        const errorMsg = e.response?.data?.error || 'Ошибка при удалении из категории';
        notifier.error({ message: errorMsg });
      } finally {
        isLoading.value = false;
      }
    }

    function addSelectedFolderToCategory(categoryId) {
      const folder = categoryModalTargetFolder.value;
      if (!folder) {
        notifier.info({ message: 'Нужно открыть модалку через контекстное меню на папке' });
        return;
      }
      addFolderToCategory(categoryId, folder.name);
    }

    function ctxAddToCategory(folder) {
      openCategoryModalForFolder(folder);
      hideContextMenu();
    }

    async function deleteCategory(categoryId) {
      if (!categoryId) return;
      if (!confirm('Вы уверены, что хотите удалить эту категорию?')) return;

      try {
        isLoading.value = true;
        const resp = await axios.delete(`data/api/categories/${categoryId}`);

        if (resp.status === 200 || resp.status === 204) {
          notifier.success({ message: 'Категория удалена' });
          await loadCategories();

          if (selectedCategoryId.value === categoryId) {
            selectedCategoryId.value = null;
            activeFilter.value = 'all';
          }
        }
      } catch (e) {
        console.error('Ошибка удаления категории:', e);
        const errorMsg = e.response?.data?.error || 'Ошибка при удалении';
        notifier.error({ message: errorMsg });
      } finally {
        isLoading.value = false;
      }
    }

    function openRenameCategoryPrompt(cat) {
      const newName = prompt('Новое имя категории', cat.name);
      if (!newName || !newName.trim()) return;
      renameCategory(cat.id, newName.trim());
    }

    async function renameCategory(categoryId, newName) {
      try {
        const resp = await axios.patch(`data/api/categories/${categoryId}/rename`, {
          categoryId,
          newName
        });

        if (resp.data?.data?.success || resp.data?.data?.id) {
          notifier.success({ message: 'Переименовано' });
          await loadCategories();
        } else {
          notifier.error({ message: resp.data?.error?.message || 'Ошибка' });
        }
      } catch (e) {
        console.error(e);
        const errorMsg = e.response?.data?.error?.message || 'Ошибка при переименовании';
        notifier.error({ message: errorMsg });
      }
    }

    const loadFolders = async () => {
      try {
        const response = await axios.get('data/api/folders');
        const rawFolders = response.data.data || [];

        folders.value = rawFolders.map(folder => ({
          ...folder,
          isPinned: !!folder.isPinned,
          isRecent: !!folder.isRecent,
          id: folder.id || folder.name
        }));

        console.log('Normalized folders:', folders.value);
      } catch (err) {
        console.error('Ошибка загрузки папок', err);
      }
    };

    const togglePin = async (folder) => {
      if (!folder?.name) return;

      try {
        isLoading.value = true;
        await axios.post(`data/api/folders/${encodeURIComponent(folder.name)}/pin`, {});
        await loadFolders();
        notifier.success({ message: `Папка ${folder.name} закреплена` });
      } catch (err) {
        const errorMsg = err.response?.data?.error || 'Ошибка при изменении статуса';
        notifier.error({
          title: 'Ошибка закрепления',
          message: errorMsg,
          autoClose: true,
        });
        console.error('togglePin error:', err);
      } finally {
        isLoading.value = false;
      }
    };

    const openFolder = async (folder) => {
      try {
        const folderName = folder.name;
        await axios.post(`data/api/folders/${encodeURIComponent(folderName)}/open`, {});
        await loadFolders();
        setTimeout(() => {
          window.location.href = folderName;
        }, 50);
      } catch (e) {
        console.error('Ошибка при открытии папки:', e);
        notifier.error({ message: 'Не удалось открыть папку' });
      }
    };

    const downloadFolder = async (folder) => {
      if (!folder) return;
      try {
        isLoading.value = true;
        const url = `data/api/folders/download?name=${encodeURIComponent(folder.name)}`;
        const response = await fetch(url);

        if (!response.ok) throw new Error('Ошибка сервера при скачивании');

        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = `${folder.name}.zip`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(downloadUrl);
        document.body.removeChild(a);
      } catch (err) {
        console.error('Ошибка при скачивании:', err);
        notifier.error({ message: 'Не удалось скачать архив. Проверьте ZipArchive на сервере.' });
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

    const closeErrorModal = () => {
      errorModal.value.isOpen = false;
    };

    const copyErrorToClipboard = async () => {
      const errorText = `Ошибка ZipArchive: ${errorModal.value.title}\n\n${errorModal.value.message}`;

      try {
        await navigator.clipboard.writeText(errorText);

        alert('Информация об ошибке скопирована в буфер обмена');
      } catch (err) {
        console.error('Не удалось скопировать текст:', err);
      }
    };

    function applyCategoryFilter(categoryId) {
      selectedCategoryId.value = categoryId;
      activeFilter.value = 'categories';
      showCategoriesPanel.value = false;
      currentPage.value = 1;
      updateTooltips();
    }

    function clearCategoryFilter() {
      selectedCategoryId.value = null;
      activeFilter.value = 'all';
      currentPage.value = 1;
      updateTooltips();
    }

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

    const pinnedCount = computed(() =>
      folders.value.filter(f => f.is_pinned === true).length
    );

    const recentCount = computed(() =>
      folders.value.filter(f => f.is_recent === true).length
    );

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
      } catch (e) { }

      if (typeof initTooltips === 'function') initTooltips()
    }

    const filterFolders = debounce(() => {
      currentPage.value = 1;
      updateTooltips();
    }, 160);

    function createContextMenuDom() {
      if (menuEl) return menuEl;

      menuEl = document.createElement('div');
      menuEl.className = 'efm-context-menu';
      menuEl.setAttribute('role', 'menu');
      menuEl.setAttribute('aria-hidden', 'true');
      menuEl.tabIndex = -1;

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
    
      <button type="button" class="ctx-item" data-action="add-to-category" role="menuitem" tabindex="-1">
        <i class="fa-solid fa-tags" aria-hidden="true"></i><span class="ctx-label">Добавить в категорию</span>
      </button>
    
      <button type="button" class="ctx-item" data-action="remove-from-category" role="menuitem" tabindex="-1">
        <i class="bi bi-folder-x" aria-hidden="true"></i><span class="ctx-label">Убрать из категории</span>
      </button>
    
      <div class="ctx-sep" role="separator"></div>
      <button type="button" class="ctx-item danger" data-action="delete" role="menuitem" tabindex="-1" data-bs-toggle="tooltip" title="Осторожно, папка удалиться без возможности восстановления!">
        <i class="bi bi-trash" aria-hidden="true"></i><span class="ctx-label">Удалить</span>
      </button>
    `;

      function createCategoryDropdown(categoriesList, parentBtn, folder, mode = 'add') {
        if (!parentBtn || !folder) return;

        const existing = parentBtn.querySelector('.ctx-category-dropdown');
        if (existing) existing.remove();

        parentBtn.classList.add('has-dropdown');

        const dropdown = document.createElement('div');
        dropdown.className = 'ctx-category-dropdown';
        dropdown.setAttribute('role', 'menu');

        if (!Array.isArray(categoriesList) || categoriesList.length === 0) {
          const emptyBtn = document.createElement('div');
          emptyBtn.className = 'ctx-item ctx-category-item disabled';
          emptyBtn.textContent =
            mode === 'add' ? 'Нет доступных категорий' : 'В этой папке нет категорий';
          dropdown.appendChild(emptyBtn);
        } else {
          categoriesList.forEach((cat) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'ctx-item ctx-category-item';
            item.setAttribute('data-cat-id', cat.id);
            item.setAttribute('role', 'menuitem');
            item.textContent = cat.name;
            dropdown.appendChild(item);
          });
        }

        parentBtn.appendChild(dropdown);

        try {
          const rect = parentBtn.getBoundingClientRect();
          const ddW = dropdown.offsetWidth || 180;
          if (rect.right + ddW + 8 > window.innerWidth) parentBtn.classList.add('flip');

          const ddH = dropdown.offsetHeight || 240;
          if (rect.bottom + ddH > window.innerHeight) parentBtn.classList.add('align-bottom');
        } catch (e) { }

        const clickHandler = (ev) => {
          const target = ev.target.closest('.ctx-category-item');
          if (!target) return;
          const catId = target.getAttribute('data-cat-id');
          if (!catId) return;

          if (mode === 'add') {
            addFolderToCategory(catId, folder.name);
          } else {
            removeFolderFromCategory(catId, folder.name);
          }
          removeDropdown();
        };

        dropdown.addEventListener('click', clickHandler);

        const outsideClick = (ev) => {
          if (!parentBtn.contains(ev.target)) {
            removeDropdown();
            document.removeEventListener('click', outsideClick);
          }
        };
        setTimeout(() => document.addEventListener('click', outsideClick), 0);

        function removeDropdown() {
          try {
            dropdown.removeEventListener('click', clickHandler);
          } catch (e) { }
          try {
            if (dropdown && dropdown.parentElement) dropdown.remove();
          } catch (e) { }
          parentBtn.classList.remove('has-dropdown', 'flip', 'align-bottom');
        }

        return {
          remove: removeDropdown,
        };
      }

      menuEl.addEventListener('click', (ev) => {
        const btn = ev.target.closest('[data-action]');
        if (!btn) return;
        ev.stopPropagation();
        ev.preventDefault();
        const action = btn.getAttribute('data-action');
        const folder = ctxFolder.value;
        console.log(folder);
        if (!folder) {
          hideContextMenu();
          return;
        }

        switch (action) {
          case 'open':
            ctxOpen(folder);
            break;
          case 'pin':
            ctxTogglePin(folder);
            break;
          case 'download':
            ctxDownload(folder);
            break;
          case 'add-to-category': {
            const folderName = ctxFolder.value?.name;
            const available = (categories.value || []).filter(
              (c) => !(Array.isArray(c.folders) && c.folders.includes(folderName))
            );
            if (!available.length) {
              notifier.info({ message: 'Папка уже везде или нет доступных категорий' });
              hideContextMenu();
              break;
            }
            createCategoryDropdown(available, btn, ctxFolder.value, 'add');
            break;
          }

          case 'remove-from-category': {
            const folderName = ctxFolder.value?.name;
            const hasCats = (categories.value || []).filter(
              (c) => Array.isArray(c.folders) && c.folders.includes(folderName)
            );
            if (!hasCats.length) {
              notifier.info({ message: 'Эта папка не привязана ни к одной категории' });
              hideContextMenu();
              break;
            }
            createCategoryDropdown(hasCats, btn, ctxFolder.value, 'remove');
            break;
          }

          case 'delete':
            ctxDelete(folder);
            break;
          case 'rename':
            ctxRename(folder);
            break;
          case 'copy':
            ctxCopyLink(folder);
            break;
          default:
            break;
        }
      });

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

      const folderName = folder?.name || '';

      const inCats = (categories.value || []).filter(
        (c) => Array.isArray(c.folders) && c.folders.includes(folderName)
      );

      const notInCats = (categories.value || []).filter(
        (c) => !(Array.isArray(c.folders) && c.folders.includes(folderName))
      );

      const addBtn = el.querySelector('[data-action="add-to-category"]');
      const removeBtn = el.querySelector('[data-action="remove-from-category"]');

      if (addBtn) {
        addBtn.style.display = notInCats.length ? '' : 'none';
      }
      if (removeBtn) {
        removeBtn.style.display = inCats.length ? '' : 'none';
      }

      const pinBtn = el.querySelector('[data-action="pin"]');
      if (pinBtn && folder) {
        const icon = pinBtn.querySelector('i');
        const label = pinBtn.querySelector('.ctx-label');

        if (folder.is_pinned) {
          if (icon) icon.className = 'bi bi-pin-fill';
          if (label) label.textContent = 'Открепить';
        } else {
          if (icon) icon.className = 'bi bi-pin';
          if (label) label.textContent = 'Закрепить';
        }
      }

      el.style.left = x + 'px';
      el.style.top = y + 'px';
      el.setAttribute('aria-hidden', 'false');
      el.classList.add('visible');

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

      const deleteBtn = el.querySelector('[data-action="delete"]');
      if (deleteBtn) {
        setTimeout(() => {
          try {

            const deleteBtnRect = deleteBtn.getBoundingClientRect();
            const screenWidth = window.innerWidth;
            const tooltipWidth = 320;
            const spaceOnRight = screenWidth - deleteBtnRect.right;
            const spaceOnLeft = deleteBtnRect.left;

            const minSpace = tooltipWidth + 20;
            const placement = (spaceOnRight >= minSpace || (spaceOnRight > spaceOnLeft && spaceOnRight >= 200)) ? 'right' : 'left';
            deleteBtn.setAttribute('data-bs-placement', placement);

            const existingTooltip = bootstrap.Tooltip.getInstance(deleteBtn);
            if (existingTooltip) {
              existingTooltip.dispose();
            }

            new bootstrap.Tooltip(deleteBtn, {
              placement: placement,
              trigger: 'hover',
              html: false,
              delay: { show: 300, hide: 100 }
            });
          } catch (e) {
            console.warn('Failed to initialize delete button tooltip:', e);
          }
        }, 50);
      }

      setTimeout(() => {
        outsideListener = (ev) => {
          if (!el.contains(ev.target)) hideContextMenu(), removeDropdown();
        };
        document.addEventListener('pointerdown', outsideListener, { capture: true });

        scrollListener = () => hideContextMenu();
        window.addEventListener('scroll', scrollListener, { passive: true, capture: true });

        keyListener = (ev) => {
          if (ev.key === 'Escape') hideContextMenu();

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

        const first = el.querySelector('.ctx-item');
        if (first) {
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
        const deleteBtn = menuEl.querySelector('[data-action="delete"]');
        if (deleteBtn) {
          const tooltip = bootstrap.Tooltip.getInstance(deleteBtn);
          if (tooltip) tooltip.dispose();
        }
      } catch (e) { }
      if (outsideListener) document.removeEventListener('pointerdown', outsideListener, { capture: true });
      if (scrollListener) window.removeEventListener('scroll', scrollListener, { capture: true });
      if (keyListener) window.removeEventListener('keydown', keyListener);
      outsideListener = null;
      scrollListener = null;
      keyListener = null;
      try { removeDropdown(); } catch (e) { }
      if (menuEl.parentNode) {
        menuEl.remove();
        menuEl = null;
      }
    }

    async function ctxTogglePin(folder) {
      await togglePin(folder);
      hideContextMenu();
    }

    const generateRandomFolders = async () => {
      try {
        isLoading.value = true;
        const randomPrefix = 'folder_' + Math.random().toString(36).substring(2, 8);

        const response = await axios.post('data/api/folders', {
          action: 'createFolders',
          count: 5,
          prefix: randomPrefix,
        });

        const result = response.data.data;

        if (result.success) {
          notifier.success({ message: '5 тестовых папок созданы!' });

          await loadFolders();
        } else {
          notifier.error({ message: 'Ошибка: ' + result.error });
        }
      } catch (error) {
        console.error('Ошибка:', error);
        notifier.error({ message: 'Ошибка при генерации папок' });
      } finally {
        isLoading.value = false;
      }
    };

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
        const resp = await axios.post(`data/api/folders/${encodeURIComponent(folder.name)}/rename`, {
          newName: newName.trim()
        });
        await loadFolders();
        notifier.success({ message: 'Папка переименована' });
      } catch (e) {
        console.error('rename error', e);
        notifier.error({ message: 'Ошибка переименования' });
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

    function showDeleteConfirm(folder) {
      return new Promise((resolve) => {
        deleteConfirmAlert.value = {
          isOpen: true,
          folder: folder,
          resolve: resolve,
        };
      });
    }

    function closeDeleteConfirm() {
      deleteConfirmAlert.value.isOpen = false;
      if (deleteConfirmAlert.value.resolve) {
        deleteConfirmAlert.value.resolve(false);
      }
      deleteConfirmAlert.value.folder = null;
      deleteConfirmAlert.value.resolve = null;
    }

    function confirmDelete() {
      if (deleteConfirmAlert.value.resolve) {
        deleteConfirmAlert.value.resolve(true);
      }
      closeDeleteConfirm();
    }

    async function ctxDelete(folder) {
      if (!folder) return hideContextMenu();
      hideContextMenu();

      const confirmed = await showDeleteConfirm(folder);
      if (!confirmed) return;

      try {
        isLoading.value = true;
        await axios.delete(`data/api/folders/${encodeURIComponent(folder.name)}`);

        await loadFolders();
        notifier.warning({ message: `Папка "${folder.name}" удалена` });
      } catch (e) {
        console.error('delete error', e);
        notifier.error({ message: 'Не удалось удалить папку' });
      } finally {
        isLoading.value = false;
      }
    }

    function onRightClickFolder(event, folder) {
      event.preventDefault();
      if (isSettingsOpen.value) return;

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

    const scrollToTop = () => window.scrollTo({ top: 0, behavior: 'smooth' });

    const formatDate = (timestamp) => {
      if (!timestamp) return '—';
      const date = new Date(timestamp * 1000);
      return date.toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    };

    const formatFolderName = (name) => {
      if (!name) return '';
      if (name.length <= 30) return name;
      return name.slice(0, 34) + '...';
    };

    const setFilter = (filter) => {
      if (filter === 'categories') {
        showCategoriesPanel.value = !showCategoriesPanel.value;
        if (showCategoriesPanel.value) {
          activeFilter.value = 'categories';
        } else {
          activeFilter.value = 'all';
          selectedCategoryId.value = null;
        }
        currentPage.value = 1;
        updateTooltips();
        return;
      }

      showCategoriesPanel.value = false;
      selectedCategoryId.value = null;
      activeFilter.value = filter;
      currentPage.value = 1;
      updateTooltips();
    };

    const onResizeHandler = debounce(() => {
      if (settings.value.perPageMode === 'auto') {
        itemsPerPage.value = calculateOptimalItemsPerPage();
        currentPage.value = 1;
        updateTooltips();
      }
    }, 200);

    function onEsc(e) {
      if (e.key === 'Escape') {
        if (isSettingsOpen.value) closeSettings();
        if (isCreateFolderModalOpen.value) closeCreateFolderModal();
        if (isCategoryModalOpen.value) closeCategoryModal();
      }
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
      if (settings.value.perPageMode === 'manual') {
        if (settings.value.perPageValue <= 0) {
          notifier.error({
            title: 'Некорректное значение',
            message: 'Количество папок на странице должно быть больше 0',
            duration: 3000
          });
          settings.value.perPageValue = 1;
          closeSettings();
        }
        if (settings.value.perPageValue > 100) {
          notifier.warning({
            title: 'Большое значение',
            message: 'Установлено максимальное значение: 100 папок на странице',
            duration: 3000
          });
          settings.value.perPageValue = 100;
        }
      }

      itemsPerPage.value = resolvedItemsPerPage.value;
      currentPage.value = 1;
      localStorage.setItem('efm_settings', JSON.stringify(settings.value));

      if (selectedThemeSetting.value && selectedThemeSetting.value !== theme.value) {
        applyTheme(selectedThemeSetting.value);
      }

      notifier.info({
        title: 'Инфо',
        message: 'Настройки изменены.',
        duration: '3000'
      })

      closeSettings();
    }

    onMounted(async () => {
      const saved = localStorage.getItem('showDbButton');
      if (saved !== null) {
        showDbButton.value = saved === 'true';
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
      } catch (_) { }

      try {
        const saved = localStorage.getItem('efm_theme');
        const systemPrefersDark =
          window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (saved && flatThemes.value.some((t) => t.id === saved)) {
          applyTheme(saved);
        } else {
          applyTheme(systemPrefersDark ? 'china' : 'japan');
        }
      } catch (e) { }

      itemsPerPage.value = resolvedItemsPerPage.value;
      await loadFolders();
      await loadCategories();
      initTooltips();
      window.addEventListener('keydown', onEsc);

      createContextMenuDom();
    });

    onBeforeUnmount(() => {
      window.removeEventListener('resize', onResizeHandler);
      window.removeEventListener('resize', () => { });
      window.removeEventListener('keydown', onEsc);
      destroyTooltips();

      try {
        if (menuEl) menuEl.remove();
      } catch (e) { }
      try {
        if (outsideListener)
          document.removeEventListener('pointerdown', outsideListener, { capture: true });
      } catch (e) { }
      try {
        if (scrollListener) window.removeEventListener('scroll', scrollListener, { capture: true });
      } catch (e) { }
      try {
        if (keyListener) window.removeEventListener('keydown', keyListener);
      } catch (e) { }
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

    return {
      folders,
      searchQuery,
      isLoading,
      activeFilter,
      currentPage,
      itemsPerPage,
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
      categories,
      isCategoryModalOpen,
      categoryModalTargetFolder,
      newCategoryName,
      loadCategories,
      openCategoryModalForFolder,
      closeCategoryModal,
      createCategory,
      addSelectedFolderToCategory,
      ctxAddToCategory,
      deleteCategory,
      openRenameCategoryPrompt,
      showCategoryChips,
      visibleCategories,
      showAllCategories,
      toggleShowAllCategories,
      selectCategory,
      selectedCategoryId,
      filteredFolders,
      removeFolderFromCategory,
      isCreateFolderModalOpen,
      newFolderName,
      folderNameInput,
      openCreateFolderModal,
      closeCreateFolderModal,
      createFolder,
      deleteConfirmAlert,
      closeDeleteConfirm,
      confirmDelete,
      snowflakes,
      snowflakeCount,
      fallSpeed,
      toggleSnow,
      toggleSidebar,
      settings,
      toggleSidebar,
      ornamentPatterns,
      getRandomOrnamentPattern,
      emptyStateContext
    };
  },
}).mount('#app');

