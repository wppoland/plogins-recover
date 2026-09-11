/**
 * Recover, settings screen behaviour.
 *
 * One job: keep the reminder plan under "Number of reminders" in step with the
 * two fields that decide it, while you are still typing. The same list is
 * rendered server-side from the saved settings, so with JavaScript off the
 * screen is correct and every field stays reachable; this only saves a merchant
 * from having to save the form to find out what they just built.
 *
 * Steps that appear ease in, steps that go ease out, because that is the whole
 * point: you should see the campaign get longer or shorter, not find a
 * different list where the old one was. Only opacity and transform move, and
 * the stylesheet drops both when the reader asked for stillness.
 */
(function () {
    'use strict';

    var STILL = window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : null;

    function stillness() {
        return !!(STILL && STILL.matches);
    }

    function intValue(field, fallback) {
        if (!field) {
            return fallback;
        }
        var parsed = parseInt(field.value, 10);

        return isNaN(parsed) ? fallback : parsed;
    }

    function makeStep(root, position, delay) {
        var item = document.createElement('li');
        item.className = 'recover-schedule__step';

        var name = document.createElement('span');
        name.className = 'recover-schedule__name';
        name.textContent = root
            .getAttribute('data-step-format')
            .replace('%s', String(position));

        var when = document.createElement('span');
        when.className = 'recover-schedule__when';
        when.textContent = root
            .getAttribute('data-when-format')
            .replace('%s', String(position * delay));

        item.appendChild(name);
        item.appendChild(when);

        return item;
    }

    function retire(item) {
        if (stillness()) {
            item.parentNode.removeChild(item);
            return;
        }

        item.classList.add('is-leaving');
        window.setTimeout(function () {
            if (item.parentNode) {
                item.parentNode.removeChild(item);
            }
        }, 180);
    }

    function sync(root, list, count, delay) {
        var max = parseInt(root.getAttribute('data-max'), 10) || 5;
        var wanted = Math.max(1, Math.min(max, count));
        var steps = list.querySelectorAll('.recover-schedule__step:not(.is-leaving)');
        var i;

        for (i = steps.length - 1; i >= wanted; i--) {
            retire(steps[i]);
        }

        for (i = steps.length; i < wanted; i++) {
            var fresh = makeStep(root, i + 1, delay);
            fresh.classList.add('is-entering');
            list.appendChild(fresh);
            window.requestAnimationFrame(function (item) {
                return function () {
                    item.classList.remove('is-entering');
                };
            }(fresh));
        }

        // Re-read: the loops above changed the list.
        var current = list.querySelectorAll('.recover-schedule__step:not(.is-leaving)');
        Array.prototype.forEach.call(current, function (item, index) {
            var when = item.querySelector('.recover-schedule__when');
            if (when) {
                when.textContent = root
                    .getAttribute('data-when-format')
                    .replace('%s', String((index + 1) * delay));
            }
        });
    }

    function init() {
        var root = document.querySelector('[data-recover-schedule]');
        var list = root ? root.querySelector('[data-recover-steps]') : null;
        var countField = document.getElementById('email_count');
        var delayField = document.getElementById('email_delay');

        if (!root || !list || !countField) {
            return;
        }

        root.classList.add('is-enhanced');

        var run = function () {
            sync(
                root,
                list,
                intValue(countField, 1),
                Math.max(0, intValue(delayField, 0))
            );
        };

        countField.addEventListener('input', run);
        countField.addEventListener('change', run);

        if (delayField) {
            delayField.addEventListener('input', run);
            delayField.addEventListener('change', run);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
