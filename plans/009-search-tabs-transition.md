# 009 — Transition the search tab content

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: LOW
- **Category**: Missed opportunities
- **Estimated scope**: 2 files, ~15 lines

## Problem

Switching between the Productos and Colecciones tabs in the search panel swaps
the whole results block instantly. The tab buttons animate their background,
but the content they control does not, so the panel feels like it reloaded.
The results section already has a 420 ms entry animation on first open; the tab
switch itself has none.

Current block in
`resources/views/livewire/storefront/search-panel.blade.php:148-205`:

```blade
            @if($tab === 'productos')
                {{-- ... product grid ... --}}
            @else
                {{-- ... collection list ... --}}
            @endif
        </section>
```

The results section is inside the Livewire component; `wire:click="setTab(...)"`
changes `$tab`, and Blade re-renders the block.

## Target

Wrap the tab content in a keyed element so Livewire replaces it on a tab
change, then animate that element on insertion:

```blade
            <div data-search-tab-panel wire:key="search-tab-{{ $tab }}">
                @if($tab === 'productos')
                    {{-- ... existing product grid, unchanged ... --}}
                @else
                    {{-- ... existing collection list, unchanged ... --}}
                @endif
            </div>
        </section>
```

Add the entry animation to `resources/css/app.css` after the existing search
results keyframes (the block that ends at line 146):

```css
@keyframes storefront-search-tab-entry {
    from {
        opacity: 0;
        transform: translateY(4px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

[data-search-tab-panel] {
    animation: storefront-search-tab-entry 150ms cubic-bezier(0.22, 1, 0.36, 1) both;
}
```

Add `[data-search-tab-panel] { animation: none; }` to the existing
`@media (prefers-reduced-motion: reduce)` block that disables the search
results entry.

## Repo conventions to follow

- The search results already use a 420 ms entry at `app.css:127-146`; this new
  animation is only for the tab content and is deliberately shorter (150 ms).
- `wire:key` is the Livewire mechanism that forces a new element when the key
  changes. When the query changes inside the same tab, the key stays the same,
  so the content morphs without replaying the animation.
- Do not add a leave animation: the old block is removed immediately. A
  crossfade would require keeping both blocks in the DOM and is out of scope.

## Steps

1. In `resources/views/livewire/storefront/search-panel.blade.php`, insert
   `<div data-search-tab-panel wire:key="search-tab-{{ $tab }}">` immediately
   before line 148.
2. Insert `</div>` immediately after the `@endif` at line 205.
3. Leave the inner product grid and collection list untouched; re-indenting is
   optional and must not change the markup.
4. In `resources/css/app.css`, add the keyframes and the
   `[data-search-tab-panel]` rule after line 146.
5. Add `[data-search-tab-panel] { animation: none; }` to the existing
   reduced-motion block for the search results.

## Boundaries

- Do NOT wrap the tab buttons; only the content block.
- Do NOT animate the suggestions aside or the whole search panel.
- Do NOT add `wire:key` to the section or the Livewire root.
- Do NOT add a leave transition or a crossfade.
- If the block does not match the excerpt, STOP and report instead of
  improvising.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Feel check**:
  - Open the search panel, type at least two characters, and switch between
    Productos and Colecciones. The new content should fade and slide 4 px over
    150 ms; the tab buttons keep their existing background transition.
  - Change the query without changing the tab. The tab content must not replay
    the entry animation.
  - Switch back and forth quickly; each new block should start from the same
    entry state without a layout jump.
  - Toggle `prefers-reduced-motion`; the content must swap instantly.
- **Done when**: the tab content has a 150 ms entry, query changes do not
  replay it, and reduced motion is instant.
