import * as formatters from '/lib/js/formatters.js';

// Функция задержки (Debounce) — ждет окончания действий пользователя
function debounce(cb, delay = 250) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            cb(...args);
        }, delay);
    };
}

// Функция пропуска кадров (Throttle) — ограничивает частоту выполнения
function throttle(cb, delay = 100) {
    let shouldWait = false;
    let waitingArgs;
    const timeoutFunc = () => {
        if (waitingArgs == null) {
            shouldWait = false;
        } else {
            cb(...waitingArgs);
            waitingArgs = null;
            setTimeout(timeoutFunc, delay);
        }
    };

    return (...args) => {
        if (shouldWait) {
            waitingArgs = args;
            return;
        }
        cb(...args);
        shouldWait = true;
        setTimeout(timeoutFunc, delay);
    };
}



// Инициализируем модалку Bootstrap один раз
const confirmModalElem = document.getElementById('confirmModal');
const bsConfirmModal = new bootstrap.Modal(confirmModalElem);
const confirmBtn = document.getElementById('confirmModalTrue');
const confirmLabel = document.getElementById('confirmModalLabel');

// Универсальная функция вызова
function askConfirmation(message) {
    if (message) confirmLabel.textContent = message;
    bsConfirmModal.show();

    return new Promise((resolve) => {
        confirmBtn.onclick = () => {
            bsConfirmModal.hide();
            resolve(true);
        };
        confirmModalElem.addEventListener('hidden.bs.modal', () => resolve(false), { once: true });
    });
}

// 1. Обработка кликов (ссылки/кнопки)
document.addEventListener('click', async (e) => {
    const target = e.target.closest('.js-confirm-action');
    if (target) {
        e.preventDefault();
        if (await askConfirmation(target.dataset.message)) {
            // Если это ссылка - переходит, если кнопка - можно добавить свою логику
            if (target.href) window.location.href = target.href;
        }
    }
});

// 2. Обработка форм
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (form.classList.contains('js-confirm-submit') && !form.dataset.confirmed) {
        e.preventDefault();
        const msg = form.getAttribute('data-message'); // проверьте нейминг дата-атрибута у себя в HTML
        if (await askConfirmation(msg)) {
            form.dataset.confirmed = "true";
            form.submit();
        }
    }
});

// 3. Автоматическая инициализация тултипов Bootstrap 5.3 (Перенесено из шаблонов)
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
});

console.log('admin_ui.js loaded');
// Внутри document.addEventListener('DOMContentLoaded', ...) в вашем admin_ui.js:

// Ограничение ввода в поле УНП (разрешаем только цифры)
document.querySelectorAll('.js-unp-mask').forEach(input => {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, ''); // Удаляем любые не-цифры на лету
    });
});

// Нативная валидация Bootstrap 5.3 для красивого подсвечивания ошибок
const forms = document.querySelectorAll('.needs-validation');
Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
});


document.addEventListener('DOMContentLoaded', function() {
    // 1. Находим все формы пагинаторов на странице
    const forms = document.querySelectorAll('.csp-paginator-form');
    if (!forms.length) return;

    // Собираем все инпуты для последующей синхронизации значений между ними
    const allInputs = document.querySelectorAll('.csp-paginator-input');

    forms.forEach(form => {
        const input = form.querySelector('.csp-paginator-input');
        const divider = form.querySelector('.csp-paginator-divider');
        const spinner = form.querySelector('.csp-paginator-spinner');

        if (!input || !divider || !spinner) return;

        const totalPages = parseInt(form.getAttribute('data-total-pages'), 10) || 1;

        // Если страница всего одна (например, в режиме "Показать всё"), блокируем инпут
        if (totalPages <= 1) {
            input.disabled = true;
            input.style.cursor = 'not-allowed';
            input.title = 'Все записи уже отображены на одной странице';
            return;
        }

        // Включение спиннера во ВСЕХ пагинаторах одновременно
        function startAllSpinners() {
            forms.forEach(f => {
                const div = f.querySelector('.csp-paginator-divider');
                const spin = f.querySelector('.csp-paginator-spinner');
                if (div) div.classList.add('d-none');
                if (spin) spin.classList.remove('d-none');
            });
        }

        // Отключение спиннера во ВСЕХ пагинаторах
        function stopAllSpinners() {
            forms.forEach(f => {
                const div = f.querySelector('.csp-paginator-divider');
                const spin = f.querySelector('.csp-paginator-spinner');
                if (div) div.classList.remove('d-none');
                if (spin) spin.classList.add('d-none');
            });
        }

        // Функция редиректа
        function triggerRedirect() {
            const p = parseInt(input.value, 10);

            if (p >= 1 && p <= totalPages) {
                const baseUrl = form.getAttribute('data-base-url');
                const limit   = form.getAttribute('data-limit');
                const sort    = form.getAttribute('data-sort');
                const dir     = form.getAttribute('data-dir');

                // Исправленный URL с обязательным сегментом /direction/
                window.location.href = baseUrl + '/page/' + p + '/limit/' + limit + '/sort/' + sort + '/' + dir;
            } else {
                stopAllSpinners();
                // Подсвечиваем ошибку на всех инпутах для наглядности
                allInputs.forEach(inp => inp.classList.add('is-invalid'));
                setTimeout(() => allInputs.forEach(inp => inp.classList.remove('is-invalid')), 800);
            }
        }

        // Дебаунс редиректа
        const debouncedRedirect = debounce(() => {
            triggerRedirect();
        }, 400);

        // Выделение текста при фокусе
        input.addEventListener('focus', function() {
            this.select();
        });

        // Обработка ввода
        input.addEventListener('input', function() {
            const currentVal = input.value;

            // Синхронизируем значение со всеми остальными пагинаторами на лету
            allInputs.forEach(inp => {
                if (inp !== input) inp.value = currentVal;
            });

            if (currentVal.trim() === '') {
                stopAllSpinners();
                return;
            }

            startAllSpinners();
            debouncedRedirect();
        });

        // Обработка отправки формы (нажатие Enter)
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            if (input.value.trim() === '') return;

            startAllSpinners();
            triggerRedirect();
        });
    });
});


document.addEventListener('DOMContentLoaded', function () {
    const btnTop = document.getElementById('btn-back-to-top');
    const btnBottom = document.getElementById('btn-go-to-bottom');

    if (!btnTop || !btnBottom) return;

    window.addEventListener('scroll', function () {
        const scrollTop = window.scrollY;
        // Общая высота документа с учетом скрытой области
        const scrollHeight = document.documentElement.scrollHeight;
        // Высота видимого окна браузера
        const clientHeight = document.documentElement.clientHeight;

        // Запас в 50 пикселей до края для фиксации конца страницы
        const isAtBottom = (scrollTop + clientHeight) >= (scrollHeight - 50);

        // Управление кнопкой "Наверх"
        if (scrollTop > 300) {
            btnTop.classList.remove('d-none');
            btnTop.style.opacity = '1';
            btnTop.style.transform = 'scale(1)';
        } else {
            btnTop.style.opacity = '0';
            btnTop.style.transform = 'scale(0.8)';
            setTimeout(() => { if (window.scrollY <= 300) btnTop.classList.add('d-none'); }, 200);
        }

        // Управление кнопкой "Вниз" (прячем, если пользователь дошел до конца страницы)
        if (isAtBottom) {
            btnBottom.style.opacity = '0';
            btnBottom.style.transform = 'scale(0.8)';
            setTimeout(() => {
                // Проверяем повторно, не ушел ли скролл вверх за время задержки
                const curBottom = (window.scrollY + document.documentElement.clientHeight) >= (document.documentElement.scrollHeight - 50);
                if (curBottom) btnBottom.classList.add('d-none');
            }, 200);
        } else {
            btnBottom.classList.remove('d-none');
            btnBottom.style.opacity = '1';
            btnBottom.style.transform = 'scale(1)';
        }
    });

    // Клик "Наверх"
    btnTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Клик "Вниз"
    btnBottom.addEventListener('click', function () {
        window.scrollTo({
            top: document.documentElement.scrollHeight,
            behavior: 'smooth'
        });
    });


// Логика сворачивания/разворачивания панели
const navBlock = document.getElementById('nav-buttons-block');
const btnToggle = document.getElementById('btn-toggle-nav');
const btnShow = document.getElementById('btn-show-nav');

if (navBlock && btnToggle && btnShow) {

    // Функция скрытия панели
    function hideNavPanel() {
        navBlock.classList.add('d-none');
        btnShow.classList.remove('d-none');
        btnShow.classList.add('d-flex');
        localStorage.setItem('paginator_nav_hidden', '1'); // Сохраняем состояние
    }

    // Функция показа панели
    function showNavPanel() {
        btnShow.classList.add('d-none');
        btnShow.classList.remove('d-flex');
        navBlock.classList.remove('d-none');
        localStorage.setItem('paginator_nav_hidden', '0'); // Сохраняем состояние
        // Принудительно вызываем событие скролла, чтобы кнопки Наверх/Вниз оценили свое текущее положение
        window.dispatchEvent(new Event('scroll'));
    }

    // Вешаем события на клики
    btnToggle.addEventListener('click', hideNavPanel);
    btnShow.addEventListener('click', showNavPanel);

    // Проверяем сохраненное состояние при загрузке страницы
    if (localStorage.getItem('paginator_nav_hidden') === '1') {
        hideNavPanel();
    }
}


















});

