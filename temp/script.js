function showDownloadSpinner(folder) {
    document.getElementById('downloadOverlay').classList.add('active');
    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: {
            ajax_download_folder: folder
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                var link = document.createElement('a');
                link.href = response.file;
                link.download = response.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                setTimeout(function() {
                    document.getElementById('downloadOverlay').classList.remove('active');
                }, 1000);
                
                setTimeout(function() {
                    $.post(window.location.href, {
                        delete_temp_file: response.file
                    });
                }, 3000);
            } else {
                document.getElementById('downloadOverlay').classList.remove('active');
                alert('Ошибка: ' + response.message);
            }
        },
        error: function() {
            document.getElementById('downloadOverlay').classList.remove('active');
            alert('Произошла ошибка при скачивании папки');
        }
    });
    return false;
}

function showLoadingBar() {
    document.querySelector('.nav-bar').classList.add('loading');
}

function hideLoadingBar() {
    document.querySelector('.nav-bar').classList.remove('loading');
}

function updateLoadingProgress(loaded, total) {
    const progress = (loaded / total) * 50;
    document.querySelector('.nav-bar').style.setProperty('--loading-progress', `${progress}%`);
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('folderSearch');
    const folderCards = document.querySelectorAll('.folder-card');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const filterButtons = document.querySelectorAll('.folder-filter');
    const gridContainer = document.querySelector('.grid-container');
    
    const pagination = document.createElement('div');
    pagination.className = 'pagination';
    gridContainer.parentNode.appendChild(pagination);
    
    const cardsPerPage = 20;
    let currentPage = 1;
    let totalPages = Math.ceil(folderCards.length / cardsPerPage);
    
    function updatePagination() {
        pagination.innerHTML = `
            <button class="pagination-button" id="prevPage" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="bi bi-chevron-left"></i> Назад
            </button>
            <span class="pagination-info">Страница ${currentPage} из ${totalPages}</span>
            <button class="pagination-button" id="nextPage" ${currentPage === totalPages ? 'disabled' : ''}>
                Вперед <i class="bi bi-chevron-right"></i>
            </button>
        `;
        
        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                updateVisibleCards();
                updatePagination();
            }
        });
        
        document.getElementById('nextPage').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                updateVisibleCards();
                updatePagination();
            }
        });
    }
    
    function updateVisibleCards() {
        const startIndex = (currentPage - 1) * cardsPerPage;
        const endIndex = startIndex + cardsPerPage;
        
        folderCards.forEach((card, index) => {
            if (index >= startIndex && index < endIndex) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    function filterCards(type, searchTerm = '') {
        showLoadingBar();
        let visibleCount = 0;
        let processedCards = 0;
        
        folderCards.forEach(card => {
            const folderTitle = card.querySelector('.folder-title');
            const folderName = folderTitle.getAttribute('title').toLowerCase();
            const isPinned = card.dataset.pinned === 'true';
            const isRecent = card.dataset.recent === 'true';
            
            let shouldShow = false;
            
            switch(type) {
                case 'pinned':
                    shouldShow = isPinned;
                    break;
                case 'recent':
                    shouldShow = isRecent;
                    break;
                default:
                    shouldShow = true;
            }
            
            if (searchTerm && !folderName.includes(searchTerm)) {
                shouldShow = false;
            }
            
            if (shouldShow) {
                card.classList.remove('hidden');
                processedCards++;
                updateLoadingProgress(processedCards, folderCards.length);
                visibleCount++;
            } else {
                card.classList.add('hidden');
                processedCards++;
                updateLoadingProgress(processedCards, folderCards.length);
            }
        });
        
        currentPage = 1;
        totalPages = Math.ceil(visibleCount / cardsPerPage);
        updatePagination();
        
        if (visibleCount === 0) {
            setTimeout(() => {
                noResultsMessage.style.display = 'flex';
                const noResultsContent = noResultsMessage.querySelector('.no-results-content');
                if (type === 'pinned') {
                    noResultsContent.innerHTML = `
                        <i class="bi bi-pin-angle" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <h3>Нет закрепленных папок</h3>
                        <p>Закрепите папки, чтобы они отображались здесь</p>
                    `;
                } else if (type === 'recent') {
                    noResultsContent.innerHTML = `
                        <i class="bi bi-clock-history" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <h3>Нет недавних папок</h3>
                        <p>Откройте папки, чтобы они появились здесь</p>
                    `;
                } else if (searchTerm !== '') {
                    noResultsContent.innerHTML = `
                        <i class="bi bi-search" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <h3>Ничего не найдено</h3>
                        <p>Попробуйте изменить параметры поиска</p>
                    `;
                }
                setTimeout(hideLoadingBar, 500);
            }, 350);
        } else {
            noResultsMessage.style.display = 'none';
            setTimeout(hideLoadingBar, 500);
        }
    }
    
    updatePagination();
    updateVisibleCards();
    
    showLoadingBar();
    let loadedCards = 0;
    const totalCards = folderCards.length;
    
    folderCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px) scale(0.95)';
    });
    
    folderCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0) scale(1)';
            loadedCards++;
            updateLoadingProgress(loadedCards, totalCards);
            
            if (loadedCards === totalCards) {
                setTimeout(hideLoadingBar, 500);
            }
        }, index * 50);
    });
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const activeType = document.querySelector('.folder-filter.active').dataset.type;
        filterCards(activeType, searchTerm);
    });
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const type = this.dataset.type;
            const searchTerm = searchInput.value.toLowerCase().trim();
            filterCards(type, searchTerm);
        });
    });
    
    const actionIcons = document.querySelectorAll('.folder-actions i');
    actionIcons.forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            this.style.transition = 'transform 0.2s, color 0.2s';
            this.style.transform = 'scale(1.2) rotate(5deg)';
        });
        
        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0)';
        });
    });
    
    searchInput.addEventListener('focus', function() {
        this.style.width = '320px';
    });
    
    searchInput.addEventListener('blur', function() {
        if (this.value === '') {
            this.style.width = '300px';
        }
    });
});