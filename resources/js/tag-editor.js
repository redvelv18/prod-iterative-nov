/**
 * Tag editor for the product show page.
 *
 * Keeps a chip list of tags, autocompletes existing tags through the
 * suggest endpoint (Enter picks a suggestion without finishing the
 * name) and submits the whole list to the server with a normal PUT
 * when "Update" is pressed.
 */

const editor = document.querySelector('[data-tag-editor]');

if (editor) {
    const list = editor.querySelector('[data-tag-list]');
    const values = editor.querySelector('[data-tag-values]');
    const input = editor.querySelector('[data-tag-input]');
    const addButton = editor.querySelector('[data-tag-add]');
    const suggestionList = editor.querySelector('[data-tag-suggestions]');
    const suggestUrl = editor.dataset.suggestUrl;

    // The mirror inputs are the source of truth for the next submit.
    let tags = Array.from(values.querySelectorAll('[data-tag-value]'))
        .map((field) => field.value);

    let suggestions = [];
    let highlighted = -1;
    let debounceTimer = null;
    let pendingRequest = null;

    // Without JavaScript the field posts its own text; with JavaScript
    // only the chips are submitted, so drop it from the form.
    input.removeAttribute('name');

    const clearSuggestions = () => {
        suggestions = [];
        highlighted = -1;
        suggestionList.hidden = true;
        suggestionList.replaceChildren();
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
    };

    const renderSuggestions = (names) => {
        suggestions = names;
        highlighted = -1;
        suggestionList.replaceChildren();

        names.forEach((name, index) => {
            const option = document.createElement('li');
            option.className = 'tag-suggestion';
            option.id = `tag-suggestion-${index}`;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.textContent = name;
            suggestionList.appendChild(option);
        });

        suggestionList.hidden = names.length === 0;
        input.setAttribute('aria-expanded', String(names.length > 0));
        input.removeAttribute('aria-activedescendant');
    };

    const highlight = (index) => {
        if (suggestions.length === 0) {
            return;
        }

        highlighted = (index + suggestions.length) % suggestions.length;

        suggestionList.querySelectorAll('[role="option"]').forEach((option, optionIndex) => {
            option.setAttribute('aria-selected', String(optionIndex === highlighted));
        });

        input.setAttribute('aria-activedescendant', `tag-suggestion-${highlighted}`);
    };

    const render = () => {
        list.replaceChildren();
        values.replaceChildren();

        if (tags.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'muted';
            empty.dataset.tagEmpty = '';
            empty.textContent = 'No tags yet.';
            list.appendChild(empty);

            return;
        }

        tags.forEach((name) => {
            const chip = document.createElement('li');
            chip.className = 'tag-chip';
            chip.dataset.tagChip = name;
            chip.append(document.createTextNode(name));

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'tag-remove';
            remove.dataset.tagRemove = name;
            remove.setAttribute('aria-label', `Remove tag ${name}`);
            remove.textContent = '×';

            chip.append(' ', remove);
            list.appendChild(chip);

            const mirror = document.createElement('input');
            mirror.type = 'hidden';
            mirror.name = 'tags[]';
            mirror.value = name;
            mirror.dataset.tagValue = '';

            values.appendChild(mirror);
        });
    };

    const addTag = (rawName) => {
        const name = rawName.trim();

        if (name === '') {
            return false;
        }

        const known = tags.some((tag) => tag.toLowerCase() === name.toLowerCase());

        if (!known) {
            tags.push(name);
            render();
        }

        input.value = '';
        clearSuggestions();

        return true;
    };

    const removeTag = (name) => {
        tags = tags.filter((tag) => tag !== name);

        render();
    };

    const suggest = async () => {
        const query = input.value.trim();

        if (query === '') {
            clearSuggestions();

            return;
        }

        pendingRequest?.abort();

        const controller = new AbortController();
        pendingRequest = controller;

        try {
            const response = await fetch(`${suggestUrl}?${new URLSearchParams({ name: query })}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                clearSuggestions();

                return;
            }

            const payload = await response.json();

            renderSuggestions(
                (payload.tags ?? []).filter(
                    (name) => !tags.some((tag) => tag.toLowerCase() === name.toLowerCase()),
                ),
            );
        } catch {
            // A cancelled or failed lookup simply keeps the list closed.
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(suggest, 150);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            highlight(highlighted + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            highlight(highlighted - 1);
        } else if (event.key === 'Escape') {
            clearSuggestions();
        } else if (event.key === 'Enter') {
            // Complete to a suggestion instead of submitting the form.
            event.preventDefault();

            const typed = input.value.trim();

            if (typed === '') {
                return;
            }

            const exact = suggestions.find((name) => name.toLowerCase() === typed.toLowerCase());
            const completion = suggestions[0];

            addTag(highlighted >= 0 ? suggestions[highlighted] : (exact ?? completion ?? typed));
        }
    });

    suggestionList.addEventListener('click', (event) => {
        const option = event.target.closest('[role="option"]');

        if (option) {
            addTag(option.textContent);
            input.focus();
        }
    });

    addButton.addEventListener('click', () => {
        addTag(input.value);
        input.focus();
    });

    list.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-tag-remove]');

        if (remove) {
            removeTag(remove.dataset.tagRemove);
        }
    });

    editor.addEventListener('submit', () => {
        // Text typed but never confirmed still joins the list.
        if (input.value.trim() !== '') {
            addTag(input.value);
        }

        clearSuggestions();
    });

    render();
}
