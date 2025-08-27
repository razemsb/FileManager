document.addEventListener('DOMContentLoaded', function () {
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

    window.PopupNotifier = PopupNotifier;
});