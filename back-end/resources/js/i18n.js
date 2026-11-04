/**
 * Interface strings for scripts. The layout prints window.ETPB_I18N from the
 * Laravel translation files, so scripts follow the same Urdu / English choice
 * as the page they run on.
 */
const dict = (typeof window !== 'undefined' && window.ETPB_I18N) || {};

export const isRtl = () => document.documentElement.getAttribute('dir') === 'rtl';

export function t(key) {
    const value = dict[key];
    return typeof value === 'string' && value !== '' ? value : key;
}

export function months() {
    return Array.isArray(dict.months) && dict.months.length === 12
        ? dict.months
        : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
}
