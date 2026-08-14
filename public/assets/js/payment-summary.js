(function (window, document) {
    'use strict';

    function toAmount(value) {
        var numericValue = Number(String(value == null ? 0 : value).replace(/\s/g, '').replace(',', '.'));
        return Number.isFinite(numericValue) ? Math.round(numericValue) : 0;
    }

    function formatAmount(value) {
        return new Intl.NumberFormat('fr-FR').format(toAmount(value)) + ' FCFA';
    }

    function resolveRoot(target) {
        if (!target) {
            return null;
        }

        if (typeof target === 'string') {
            return document.querySelector(target);
        }

        return target.nodeType === 1 ? target : null;
    }

    function createLine(line, index) {
        var row = document.createElement('div');
        row.className = 'payment-summary__line';
        row.setAttribute('data-payment-summary-line', '');

        if (line && line.key != null) {
            row.setAttribute('data-key', String(line.key));
        } else {
            row.setAttribute('data-key', String(index));
        }

        var label = document.createElement('span');
        label.className = 'payment-summary__label';
        label.setAttribute('data-payment-summary-label', '');
        label.textContent = line && line.label != null ? String(line.label) : '';

        var amount = document.createElement('span');
        amount.className = 'payment-summary__amount';
        amount.setAttribute('data-payment-summary-amount', '');
        amount.setAttribute('data-amount', line && line.amount != null ? String(line.amount) : '0');
        amount.textContent = formatAmount(line && line.amount != null ? line.amount : 0);

        row.appendChild(label);
        row.appendChild(amount);
        return row;
    }

    /**
     * Updates presentation only. It never reads or changes form controls.
     *
     * @param {Element|string} target A summary element or a selector.
     * @param {Array<{label: string, amount: number, key?: string}>} lines
     * @param {number} total
     * @returns {boolean} Whether a summary was found and updated.
     */
    function updatePaymentSummary(target, lines, total) {
        var root = resolveRoot(target);
        if (!root || !root.matches('[data-payment-summary]')) {
            return false;
        }

        if (Array.isArray(lines)) {
            var lineContainer = root.querySelector('[data-payment-summary-lines]');
            if (lineContainer) {
                lineContainer.replaceChildren.apply(lineContainer, lines.map(createLine));
            }

            var divider = root.querySelector('[data-payment-summary-divider]');
            if (divider) {
                divider.hidden = lines.length === 0;
            }
        }

        if (total !== undefined) {
            var totalElement = root.querySelector('[data-payment-summary-total]');
            if (totalElement) {
                totalElement.setAttribute('data-amount', String(total));
                totalElement.textContent = formatAmount(total);
            }
        }

        return true;
    }

    window.DressurPaymentSummary = {
        formatAmount: formatAmount,
        update: updatePaymentSummary
    };
})(window, document);