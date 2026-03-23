/**
 * MonoTalk - frontend trust helpers
 *
 * Mirrors the backend trust rules so UI badges can be rendered consistently
 * without changing the existing leveling module.
 */
(function () {
    function clampTrust(trust) {
        return Math.max(0, Math.min(100, Number(trust) || 0));
    }

    function addTrust(user, amount) {
        return {
            ...user,
            trust: clampTrust((Number(user?.trust) || 50) + Math.max(0, Number(amount) || 0))
        };
    }

    function removeTrust(user, amount) {
        return {
            ...user,
            trust: clampTrust((Number(user?.trust) || 50) - Math.max(0, Number(amount) || 0))
        };
    }

    function getTrustStatus(trust) {
        const value = clampTrust(trust);

        if (value <= 30) {
            return {
                label: 'Suspicious',
                color: 'red',
                icon: '🔴',
                className: 'trust-badge-danger'
            };
        }

        if (value <= 70) {
            return {
                label: 'Neutral',
                color: 'yellow',
                icon: '🟡',
                className: 'trust-badge-warning'
            };
        }

        return {
            label: 'Trusted',
            color: 'green',
            icon: '🟢',
            className: 'trust-badge-success'
        };
    }

    function renderTrustText(trust) {
        const value = clampTrust(trust);
        const status = getTrustStatus(value);
        return `${status.icon} ${status.label} (${value}%)`;
    }

    window.TrustSystem = {
        addTrust,
        removeTrust,
        getTrustStatus,
        clampTrust,
        renderTrustText
    };
})();
