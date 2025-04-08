const { 
    createApp, 
    ref, 
    computed, 
    onMounted, 
    nextTick 
} = Vue;

createApp({
    setup() {
        const folders = ref([]);
        const searchQuery = ref('');
        const isLoading = ref(false);
        const activeFilter = ref('all');
        const currentPage = ref(1);
        const itemsPerPage = ref(16);
        const sidebarCollapsed = ref(false);
        const error = ref(null);
        const tooltipInstances = ref([]);

        const initTooltips = () => {
            destroyTooltips();
            const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipInstances.value = Array.from(tooltipElements).map(el => {
                return new bootstrap.Tooltip(el, {
                    placement: 'bottom',
                    trigger: 'hover'
                });
            });
        }; 

        const loadFolders = async () => {
            try {
                isLoading.value = true;
                const response = await axios.get('temp/api.php?action=getFolders');
                if (response.data?.success) {
                    folders.value = response.data.data;
                    initTooltips();
                } else {
                    throw new Error(response.data?.error || 'Ошибка загрузки данных');
                }
            } catch (err) {
                error.value = err.message;
                console.error('Ошибка:', err);
            } finally {
                isLoading.value = false;
            }
        };

        const destroyTooltips = () => {
            tooltipInstances.value.forEach(tooltip => {
                tooltip.dispose();
            });
            tooltipInstances.value = [];
        };

        const setFilter = (filter) => {
            activeFilter.value = filter;
            currentPage.value = 1;
            updateTooltips();
        };

        const updateTooltips = () => {
            nextTick(() => {
                setTimeout(initTooltips, 100);
            });
        };

        const toggleSidebar = () => {
            sidebarCollapsed.value = !sidebarCollapsed.value;
        };

        const togglePin = async (folder) => {
            try {
                isLoading.value = true;
                const response = await axios.post('temp/api.php', {
                    action: 'togglePin',
                    folder: folder.name,
                    pinned: folder.isPinned
                });
                if (!response.data?.success) {
                    throw new Error(response.data?.error || 'Ошибка при изменении статуса');
                }
                await loadFolders();
            } catch (err) {
                error.value = err.message;
                console.error('Ошибка:', err);
            } finally {
                isLoading.value = false;
            }
        };

        const openFolder = async (folder) => {
            try {
                await axios.post('temp/api.php', {
                    action: 'addRecent',
                    folder: folder.name
                });
                window.location.href = folder.name;
            } catch (err) {
                error.value = 'Не удалось открыть папку';
                console.error('Ошибка:', err);
            }
        };

        const downloadFolder = (folder) => {
            isLoading.value = true;
            window.open(`temp/api.php?action=download&folder=${encodeURIComponent(folder.name)}`, '_blank');
            isLoading.value = false;
        };

        const nextPage = () => {
            if (currentPage.value < totalPages.value) {
                currentPage.value++;
                updateTooltips();
            }
        };

        const prevPage = () => {
            if (currentPage.value > 1) {
                currentPage.value--;
                updateTooltips();
            }
        };

        const goToPage = (page) => {
            if (page !== '...') {
                currentPage.value = page;
                updateTooltips();
            }
        };

        const formatDate = (timestamp) => {
            return new Date(timestamp * 1000).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };

        const formatFolderName = (name) => {
            if (name.length <= 8) return name;
            const truncated = [...name].slice(0, 8).join('');
            return `${truncated}...`;
        };

        const filteredFolders = computed(() => {
            let result = folders.value;
            if (activeFilter.value === 'pinned') {
                result = result.filter(f => f.isPinned);
            } else if (activeFilter.value === 'recent') {
                result = result.filter(f => f.isRecent);
            }
            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                result = result.filter(f => f.name.toLowerCase().includes(query));
            }
            return result;
        });

        const totalCount = computed(() => folders.value.length);
        const pinnedCount = computed(() => folders.value.filter(f => f.isPinned).length);
        const recentCount = computed(() => folders.value.filter(f => f.isRecent).length);
        const totalPages = computed(() => Math.ceil(filteredFolders.value.length / itemsPerPage.value));

        const paginatedFolders = computed(() => {
            const start = (currentPage.value - 1) * itemsPerPage.value;
            const end = start + itemsPerPage.value;
            return filteredFolders.value.slice(start, end);
        });

        const visiblePages = computed(() => {
            const pages = [];
            const range = 2;
            for (let i = 1; i <= totalPages.value; i++) {
                if (i === 1 || i === totalPages.value || 
                    (i >= currentPage.value - range && i <= currentPage.value + range)) {
                    pages.push(i);
                } else if (i === currentPage.value - range - 1 || i === currentPage.value + range + 1) {
                    pages.push('...');
                }
            }
            return pages;
        });

        onMounted(() => {
            loadFolders();
            const checkScreenSize = () => {
                sidebarCollapsed.value = window.innerWidth < 992;
            };
            window.addEventListener('resize', checkScreenSize);
            checkScreenSize();
        });

        return {
            folders,
            searchQuery,
            isLoading,
            activeFilter,
            currentPage,
            sidebarCollapsed,
            error,
            filteredFolders,
            totalCount,
            pinnedCount,
            recentCount,
            totalPages,
            paginatedFolders,
            visiblePages,
            loadFolders,
            setFilter,
            toggleSidebar,
            togglePin,
            openFolder,
            downloadFolder,
            nextPage,
            prevPage,
            goToPage,
            formatDate,
            formatFolderName
        };
    }
}).mount('#app');
