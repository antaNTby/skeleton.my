export function formatUsd(
    price = 1.0,
    rval = 4,
    decimalSeparator = '.',
    thousandsSeparator = '',
    unitSymbol = '',
) {
    return (
        unitSymbol +
        formatPrice(price, rval, decimalSeparator, thousandsSeparator)
    );
}

export function formatPrice(
    price,
    rval = 2,
    decimalSeparator = '.',
    thousandsSeparator = '',
) {
    // Проверка входных данных
    if (isNaN(price)) {
        return '1.0000'; // Возвращает строку по аналогии с PHP
    }

    return Number(price).toFixed(rval).replace('.', decimalSeparator);
}

export function formatUnp(str) {
    // Удаляем всё, кроме цифр
    const digits = str.replace(/\D/g, '');
    // Преобразуем в формат 123 456 789
    // console.log(formatUnp("12-34.56/789")); // Выведет: 123 456 789
    return digits.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
}

/**
 * Форматирует секунды в строку "N ч.", "N мин." или "N сек."
 * @param {number} seconds
 * @returns {string}
 */
export function formatSessionTime(seconds) {
    seconds = Math.max(0, +seconds); // Защита от отрицательных чисел

    if (seconds >= 3600) {
        const hours = Math.floor(seconds / 3600);
        return hours + ' ч.';
    } else if (seconds >= 60) {
        const minutes = Math.floor(seconds / 60);
        return minutes + ' мин.';
    } else {
        return seconds + ' сек.';
    }
}