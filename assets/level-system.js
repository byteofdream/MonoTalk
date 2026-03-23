/**
 * MonoTalk - frontend leveling helpers
 *
 * This file keeps level math separate from DOM updates so we can reuse the
 * same logic in profile widgets, notifications, and future gamification UI.
 */
(function () {
    const xpNumberFormatter = new Intl.NumberFormat();

    function normalizeLevelsConfig(levelsConfig) {
        const fallback = {
            levels: [
                { level: 1, xp_required: 0, name: 'Newbie' },
                { level: 2, xp_required: 60, name: 'Starter' },
                { level: 3, xp_required: 140, name: 'Beginner' },
                { level: 4, xp_required: 240, name: 'Explorer' },
                { level: 5, xp_required: 360, name: 'Talker' },
                { level: 6, xp_required: 520, name: 'Contributor' },
                { level: 7, xp_required: 720, name: 'Regular' },
                { level: 8, xp_required: 980, name: 'Engaged' },
                { level: 9, xp_required: 1300, name: 'Active' },
                { level: 10, xp_required: 1680, name: 'Skilled' },
                { level: 11, xp_required: 2120, name: 'Trusted' },
                { level: 12, xp_required: 2640, name: 'Advanced' },
                { level: 13, xp_required: 3240, name: 'Veteran' },
                { level: 14, xp_required: 3920, name: 'Pro' },
                { level: 15, xp_required: 4700, name: 'Specialist' },
                { level: 16, xp_required: 5580, name: 'Elite' },
                { level: 17, xp_required: 6560, name: 'Champion' },
                { level: 18, xp_required: 7660, name: 'Master' },
                { level: 19, xp_required: 8880, name: 'Grandmaster' },
                { level: 20, xp_required: 10240, name: 'Legend' },
                { level: 21, xp_required: 11740, name: 'Mythic' },
                { level: 22, xp_required: 13400, name: 'Immortal' },
                { level: 23, xp_required: 15240, name: 'Eternal' },
                { level: 24, xp_required: 17280, name: 'Cosmic' },
                { level: 25, xp_required: 19540, name: 'Forum Titan' }
            ]
        };

        const entries = Array.isArray(levelsConfig?.levels) && levelsConfig.levels.length
            ? levelsConfig.levels
            : fallback.levels;

        return {
            levels: entries
                .map((level, index) => ({
                    level: Math.max(1, Number(level?.level) || index + 1),
                    xp_required: Math.max(0, Number(level?.xp_required) || 0),
                    name: String(level?.name || `Level ${index + 1}`)
                }))
                .sort((a, b) => (a.xp_required - b.xp_required) || (a.level - b.level))
        };
    }

    function calculateLevel(xp, levelsConfig) {
        const safeXp = Math.max(0, Number(xp) || 0);
        const normalized = normalizeLevelsConfig(levelsConfig);
        let currentLevel = normalized.levels[0];

        normalized.levels.forEach((level) => {
            if (safeXp >= level.xp_required) {
                currentLevel = level;
            }
        });

        return currentLevel;
    }

    function getNextLevelXP(currentLevel, levelsConfig) {
        const normalized = normalizeLevelsConfig(levelsConfig);
        const nextLevel = normalized.levels.find((level) => level.level === Number(currentLevel) + 1);
        return nextLevel ? nextLevel.xp_required : null;
    }

    function buildProgress(user, levelsConfig) {
        const safeUser = {
            xp: Math.max(0, Number(user?.xp) || 0),
            level: Math.max(1, Number(user?.level) || 1)
        };
        const currentLevel = calculateLevel(safeUser.xp, levelsConfig);
        const nextLevelXP = getNextLevelXP(currentLevel.level, levelsConfig);
        const currentFloorXP = currentLevel.xp_required;
        const progressSpan = nextLevelXP === null ? Math.max(1, safeUser.xp) : Math.max(1, nextLevelXP - currentFloorXP);
        const progressValue = nextLevelXP === null ? safeUser.xp : Math.max(0, safeUser.xp - currentFloorXP);

        return {
            xp: safeUser.xp,
            level: currentLevel.level,
            level_name: currentLevel.name,
            next_level_xp: nextLevelXP,
            progress_percent: nextLevelXP === null ? 100 : Math.min(100, Math.round((progressValue / progressSpan) * 100)),
            max_level: nextLevelXP === null
        };
    }

    function addXP(user, amount, levelsConfig) {
        const previous = calculateLevel(user?.xp, levelsConfig);
        const updatedUser = {
            ...user,
            xp: Math.max(0, Number(user?.xp) || 0) + Math.max(0, Number(amount) || 0)
        };
        const current = calculateLevel(updatedUser.xp, levelsConfig);
        updatedUser.level = current.level;

        return {
            user: updatedUser,
            leveledUp: current.level > previous.level,
            progress: buildProgress(updatedUser, levelsConfig)
        };
    }

    function renderProgressNote(leveling) {
        if (!leveling || leveling.max_level) {
            return 'Max level reached';
        }

        const xpLeft = Math.max(0, Number(leveling.next_level_xp || 0) - Number(leveling.xp || 0));
        return `${xpLeft} XP until next level`;
    }

    function updateLevelUI(container, leveling) {
        if (!container || !leveling) return;

        const progressMax = leveling.next_level_xp ?? leveling.progress_max ?? leveling.xp ?? 0;
        const levelValue = container.querySelector('[data-level-value]');
        const levelName = container.querySelector('[data-level-name]');
        const progressText = container.querySelector('[data-level-progress-text]');
        const progressBar = container.querySelector('[data-level-progress-bar]');
        const progressNote = container.querySelector('[data-level-note]');

        if (levelValue) levelValue.textContent = String(leveling.level ?? 1);
        if (levelName) levelName.textContent = String(leveling.level_name ?? 'Newbie');
        if (progressText) {
            progressText.textContent = `${xpNumberFormatter.format(Number(leveling.xp || 0))} / ${xpNumberFormatter.format(Number(progressMax || 0))}`;
        }
        if (progressBar) progressBar.style.width = `${Math.max(0, Math.min(100, Number(leveling.progress_percent || 0)))}%`;
        if (progressNote) progressNote.textContent = renderProgressNote(leveling);

        container.dataset.leveling = JSON.stringify(leveling);
    }

    function showLevelUpNotification(leveling) {
        if (!leveling?.level_up || !leveling?.progress?.level) return;

        const message = leveling.message || `You reached level ${leveling.progress.level}!`;
        if (typeof window.showAppPopup === 'function') {
            window.showAppPopup(message, { title: 'Level up', severity: 'low' });
            return;
        }

        window.alert(message);
    }

    function applyLevelingUpdate(leveling) {
        if (!leveling || !leveling.progress) return;

        const profileCard = document.getElementById('profileLevelCard');
        updateLevelUI(profileCard, leveling.progress);
        showLevelUpNotification(leveling);
        window.dispatchEvent(new CustomEvent('user-level-updated', { detail: leveling }));
    }

    function mountProfileCard() {
        const profileCard = document.getElementById('profileLevelCard');
        if (!profileCard) return;

        try {
            const payload = JSON.parse(profileCard.dataset.leveling || '{}');
            updateLevelUI(profileCard, payload);
        } catch (error) {
            console.error('Failed to initialize level card', error);
        }
    }

    window.LevelSystem = {
        addXP,
        calculateLevel,
        getNextLevelXP,
        buildProgress,
        updateLevelUI,
        applyLevelingUpdate,
        mountProfileCard
    };
})();
