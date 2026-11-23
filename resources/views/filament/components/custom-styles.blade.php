<!-- Preconnect for Performance & LCP -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"></noscript>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" />

<style>
    [x-cloak] {
        display: none !important;
    }

    /* ==========================================================================
       NETSPEED CYBER NOC & BROADBAND DESIGN SYSTEM (FILAMENT ADMIN)
       Theme Tokens:
       --bg:       #0c0e0d (Canvas Background)
       --surface:  #131615 (Cards, Sections, Tables)
       --surface2: #1a1e1c (Inputs, Metric Subcards)
       --border:   #232826 (Borders, Dividers)
       --text:     #eef1f0 (Headings, Primary Text)
       --muted:    #9aa49f (Subtitles, Labels, Captions)
       --accent:   #00e07e (Electric Mint Neon Primary)
       --accent2:  #4deda4 (Hover Mint)
       --ink:      #06261a (Deep Emerald Text on Mint Buttons)
       --soft:     #0f3a25 (Dark Emerald Container)
       --danger:   #f27b7b (Soft Red)
       --warn:     #f2c56b (Soft Amber)
       ========================================================================== */

    :root {
        /* Light Mode Tokens */
        --bg: #f8fafc;
        --surface: #ffffff;
        --surface2: #f1f5f9;
        --border: #e2e8f0;
        --text: #0f172a;
        --muted: #64748b;
        --accent: #00e07e;
        --accent2: #4deda4;
        --ink: #06261a;
        --soft: #ecfdf5;
        --danger: #ef4444;
        --warn: #f59e0b;
        --chk: #ffffff;
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        --btn: #00e07e;
        --btnText: #ffffff;
    }

    .dark, html.dark {
        /* Dark Mode Tokens */
        --bg: #0c0e0d;
        --surface: #131615;
        --surface2: #1a1e1c;
        --border: #232826;
        --text: #eef1f0;
        --muted: #9aa49f;
        --accent: #00e07e;
        --accent2: #4deda4;
        --ink: #06261a;
        --soft: #0f3a25;
        --danger: #f27b7b;
        --warn: #f2c56b;
        --chk: #191d1b;
        --shadow: 0 1px 0 #ffffff08 inset, 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        --btn: #00e07e;
        --btnText: #ffffff;
    }

    body,
    input,
    button,
    select,
    textarea,
    p,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    span:not(.material-symbols-rounded):not([class*="material-symbols"]) {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }

    /* Google Material Symbols Rounded Icons System */
    .material-symbols-rounded {
        font-family: 'Material Symbols Rounded' !important;
        font-weight: normal !important;
        font-style: normal !important;
        font-size: 20px;
        line-height: 1 !important;
        letter-spacing: normal !important;
        text-transform: none !important;
        display: inline-block !important;
        white-space: nowrap !important;
        word-wrap: normal !important;
        direction: ltr !important;
        -webkit-font-feature-settings: 'liga' !important;
        -webkit-font-smoothing: antialiased !important;
        font-feature-settings: 'liga' !important;
        font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24 !important;
        vertical-align: middle !important;
        user-select: none !important;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* 1. GLOBAL BACKGROUNDS & LAYOUT CANVAS */
    body,
    .dark body,
    .fi-layout,
    .dark .fi-layout,
    .fi-main,
    .dark .fi-main,
    .fi-topbar-ctn,
    .dark .fi-topbar-ctn {
        background-color: var(--bg) !important;
        color: var(--text);
    }

    /* Defensive Sizing: Prevent unconstrained SVGs inside form placeholders / previews */
    .fi-fo-placeholder svg,
    .fi-page-content svg.w-6,
    .fi-page-content svg.w-5,
    .fi-page-content svg.w-4 {
        max-width: 24px !important;
        max-height: 24px !important;
    }

    /* Selection Color */
    ::selection {
        background-color: var(--accent) !important;
        color: var(--ink) !important;
    }

    /* 2. SIDEBAR STYLING */
    .fi-sidebar,
    .dark .fi-sidebar {
        background-color: var(--surface) !important;
        border-right: 1px solid var(--border) !important;
    }

    .fi-sidebar-header,
    .dark .fi-sidebar-header {
        border-bottom: 1px solid var(--border) !important;
        background-color: var(--surface) !important;
    }

    /* Sidebar Navigation Active State */
    .fi-sidebar-item-active > a,
    .fi-sidebar-item-active > button,
    .dark .fi-sidebar-item-active > a,
    .dark .fi-sidebar-item-active > button {
        background-color: rgba(0, 224, 126, 0.12) !important;
        color: var(--text) !important;
        border-left: 3px solid var(--accent) !important;
        border-radius: 0 0.625rem 0.625rem 0 !important;
        font-weight: 700 !important;
    }

    .fi-sidebar-item-active svg,
    .fi-sidebar-item-active .fi-sidebar-item-icon {
        color: #059669 !important;
    }
    .dark .fi-sidebar-item-active svg,
    .dark .fi-sidebar-item-active .fi-sidebar-item-icon {
        color: var(--accent) !important;
    }

    .fi-sidebar-item:not(.fi-sidebar-item-active) > a:hover,
    .fi-sidebar-item:not(.fi-sidebar-item-active) > button:hover,
    .dark .fi-sidebar-item:not(.fi-sidebar-item-active) > a:hover,
    .dark .fi-sidebar-item:not(.fi-sidebar-item-active) > button:hover {
        background-color: var(--surface2) !important;
        color: var(--text) !important;
    }

    .fi-sidebar-group-label,
    .dark .fi-sidebar-group-label {
        color: var(--muted) !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.08em !important;
        font-size: 0.7rem !important;
    }

    /* 3. TOPBAR GLASSMORPHISM */
    .fi-topbar,
    .dark .fi-topbar {
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        background-color: var(--surface) !important;
        border-bottom: 1px solid var(--border) !important;
    }

    /* 4. CARDS, SECTIONS, & SURFACES */
    .fi-section,
    .fi-wi-widget > div,
    .fi-ta-ctn,
    .fi-modal-window,
    .fi-dropdown-panel,
    .dark .fi-section,
    .dark .fi-wi-widget > div,
    .dark .fi-ta-ctn,
    .dark .fi-modal-window,
    .dark .fi-dropdown-panel {
        border-radius: 1rem !important;
        border: 1px solid var(--border) !important;
        background-color: var(--surface) !important;
        box-shadow: var(--shadow) !important;
    }

    .fi-section,
    .dark .fi-section {
        transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
        overflow: hidden !important;
    }

    .fi-section:hover,
    .dark .fi-section:hover {
        border-color: rgba(0, 224, 126, 0.3) !important;
    }

    .fi-section-header,
    .dark .fi-section-header {
        background-color: var(--surface2) !important;
        padding: 1rem 1.25rem !important;
        border-bottom: 1px solid var(--border) !important;
    }

    .fi-section-content,
    .dark .fi-section-content {
        padding: 1.25rem !important;
    }

    /* Modern Tabs Card Bar */
    .fi-sc-tabs-tablist,
    .fi-tabs,
    .dark .fi-sc-tabs-tablist,
    .dark .fi-tabs {
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        border-radius: 0.875rem !important;
        padding: 0.35rem !important;
        gap: 0.4rem !important;
        margin-bottom: 1.25rem !important;
    }

    .fi-sc-tabs-tab,
    .fi-tabs-item,
    .dark .fi-sc-tabs-tab,
    .dark .fi-tabs-item {
        border-radius: 0.625rem !important;
        padding: 0.55rem 1.15rem !important;
        font-size: 0.8125rem !important;
        font-weight: 700 !important;
        transition: all 0.2s ease !important;
        border: 1px solid transparent !important;
        color: var(--muted) !important;
    }

    .fi-sc-tabs-tab[aria-selected="true"],
    .fi-tabs-item[aria-selected="true"],
    .fi-tabs-item--active,
    .dark .fi-sc-tabs-tab[aria-selected="true"],
    .dark .fi-tabs-item[aria-selected="true"],
    .dark .fi-tabs-item--active {
        background: var(--btn) !important;
        color: #ffffff !important;
        font-weight: 800 !important;
        box-shadow: 0 4px 14px rgba(0, 224, 126, 0.35) !important;
        border-color: var(--btn) !important;
    }

    .fi-sc-tabs-tab[aria-selected="true"] *,
    .fi-tabs-item[aria-selected="true"] *,
    .fi-tabs-item--active *,
    .dark .fi-sc-tabs-tab[aria-selected="true"] *,
    .dark .fi-tabs-item[aria-selected="true"] *,
    .dark .fi-tabs-item--active * {
        color: #ffffff !important;
    }

    .fi-modal-footer,
    .dark .fi-modal-footer {
        border-top: 1px solid var(--border) !important;
        background-color: var(--surface) !important;
    }

    /* 5. METRIC CARDS & STAT OVERVIEW */
    .dark .fi-wi-stats-overview-stat,
    .dark .isp-metric-card,
    .dark .noc-subcard {
        border-radius: 0.875rem !important;
        background-color: var(--surface2) !important;
        border: 1px solid var(--border) !important;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s ease !important;
    }

    .dark .fi-wi-stats-overview-stat:hover,
    .dark .isp-metric-card:hover,
    .dark .noc-subcard:hover {
        transform: translateY(-2px) !important;
        border-color: rgba(0, 224, 126, 0.35) !important;
        box-shadow: 0 8px 25px -4px rgba(0, 0, 0, 0.7), 0 0 16px rgba(0, 224, 126, 0.15) !important;
    }

    .dark .fi-wi-stats-overview-stat-value {
        color: #ffffff !important;
        font-weight: 900 !important;
        letter-spacing: -0.02em !important;
    }

    .dark .fi-wi-stats-overview-stat-label {
        color: var(--muted) !important;
        font-weight: 700 !important;
        font-size: 0.75rem !important;
    }

    /* 6. PRIMARY BUTTONS & ACTIONS (NEON MINT STYLE) */
    .fi-btn-primary,
    .fi-btn-color-primary,
    .dark .fi-btn-primary,
    .dark .fi-btn-color-primary,
    button[type="submit"].fi-btn:not(.fi-btn-outlined),
    .dark button[type="submit"].fi-btn:not(.fi-btn-outlined),
    .fi-ac-btn-action.fi-color-primary,
    .dark .fi-ac-btn-action.fi-color-primary {
        background-color: var(--btn) !important;
        color: #ffffff !important;
        font-weight: 800 !important;
        border: none !important;
        box-shadow: 0 4px 14px rgba(0, 224, 126, 0.25) !important;
        transition: all 0.2s ease-in-out !important;
    }

    .fi-btn-primary *,
    .fi-btn-color-primary *,
    .dark .fi-btn-primary *,
    .dark .fi-btn-color-primary *,
    button[type="submit"].fi-btn:not(.fi-btn-outlined) *,
    .dark button[type="submit"].fi-btn:not(.fi-btn-outlined) *,
    .fi-ac-btn-action.fi-color-primary *,
    .dark .fi-ac-btn-action.fi-color-primary * {
        color: #ffffff !important;
    }

    .fi-btn-primary:hover,
    .fi-btn-color-primary:hover,
    .dark .fi-btn-primary:hover,
    .dark .fi-btn-color-primary:hover,
    button[type="submit"].fi-btn:not(.fi-btn-outlined):hover,
    .dark button[type="submit"].fi-btn:not(.fi-btn-outlined):hover,
    .fi-ac-btn-action.fi-color-primary:hover,
    .dark .fi-ac-btn-action.fi-color-primary:hover {
        background-color: var(--accent2) !important;
        color: #ffffff !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 6px 20px rgba(0, 224, 126, 0.45) !important;
    }

    .fi-btn-primary:hover *,
    .fi-btn-color-primary:hover *,
    .dark .fi-btn-primary:hover *,
    .dark .fi-btn-color-primary:hover *,
    button[type="submit"].fi-btn:not(.fi-btn-outlined):hover *,
    .dark button[type="submit"].fi-btn:not(.fi-btn-outlined):hover *,
    .fi-ac-btn-action.fi-color-primary:hover *,
    .dark .fi-ac-btn-action.fi-color-primary:hover * {
        color: #ffffff !important;
    }

    /* RIGHT-ALIGNED FORM & MODAL ACTION BUTTONS */
    .fi-form-actions,
    .fi-fo-actions,
    .fi-page-content form > div.fi-ac:last-child,
    .fi-page-content form > .fi-ac.fi-align-start,
    .fi-ac.fi-align-start:not(.fi-ta-actions):not(.fi-ta-cell *),
    .fi-page-sub-navigation-actions,
    .fi-modal-footer,
    .fi-modal-footer-actions,
    .fi-modal-footer .fi-ac {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        width: 100% !important;
        gap: 0.75rem !important;
    }

    .fi-form-actions > .fi-ac,
    .fi-fo-actions > .fi-ac {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        width: 100% !important;
        gap: 0.75rem !important;
    }

    /* Secondary / Gray Buttons */
    .fi-btn-outlined,
    .fi-btn-color-gray,
    .dark .fi-btn-outlined,
    .dark .fi-btn-color-gray {
        background-color: #06261a !important;
        border: 1px solid rgba(0, 224, 126, 0.3) !important;
        color: #ffffff !important;
    }

    .fi-btn-outlined *,
    .fi-btn-color-gray *,
    .dark .fi-btn-outlined *,
    .dark .fi-btn-color-gray * {
        color: #ffffff !important;
    }

    .fi-btn-outlined:hover,
    .fi-btn-color-gray:hover,
    .dark .fi-btn-outlined:hover,
    .dark .fi-btn-color-gray:hover {
        background-color: #0f3a25 !important;
        border-color: rgba(0, 224, 126, 0.5) !important;
        color: #ffffff !important;
    }

    /* Danger Buttons */
    .fi-btn-color-danger,
    .dark .fi-btn-color-danger {
        background-color: #ef4444 !important;
        border: 1px solid #dc2626 !important;
        color: #ffffff !important;
    }

    .fi-btn-color-danger *,
    .dark .fi-btn-color-danger * {
        color: #ffffff !important;
    }

    .fi-btn-color-danger:hover,
    .dark .fi-btn-color-danger:hover {
        background-color: #dc2626 !important;
        color: #ffffff !important;
    }

    /* 7. TABLES STYLING */
    .fi-ta-ctn,
    .dark .fi-ta-ctn {
        background-color: var(--surface) !important;
        border: 1px solid var(--border) !important;
    }

    .fi-ta-header,
    .dark .fi-ta-header {
        border-bottom: 1px solid var(--border) !important;
    }

    .fi-ta-table th,
    .dark .fi-ta-table th {
        background-color: var(--surface2) !important;
        border-bottom: 1px solid var(--border) !important;
        color: var(--muted) !important;
        font-weight: 700 !important;
        font-size: 0.7rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.06em !important;
        padding-top: 0.875rem !important;
        padding-bottom: 0.875rem !important;
    }

    .fi-ta-table td,
    .dark .fi-ta-table td {
        border-bottom: 1px solid var(--border) !important;
        color: var(--text) !important;
        font-size: 0.8125rem !important;
    }

    .fi-ta-row:hover,
    .dark .fi-ta-row:hover {
        background-color: var(--surface2) !important;
    }

    .fi-ta-pagination,
    .dark .fi-ta-pagination {
        border-top: 1px solid var(--border) !important;
        background-color: var(--surface) !important;
    }

    /* 8. FORM CONTROLS (INPUTS, SELECTS, TEXTAREAS) */
    .fi-input-wrp,
    .dark .fi-input-wrp {
        background-color: var(--surface2) !important;
        border: 1px solid var(--border) !important;
        border-radius: 0.75rem !important;
        box-shadow: none !important;
    }

    .fi-input-wrp:focus-within,
    .dark .fi-input-wrp:focus-within {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 1px var(--accent) !important;
    }

    .fi-input-wrp input,
    .fi-input-wrp textarea,
    .fi-select-input,
    .dark .fi-input-wrp input,
    .dark .fi-input-wrp textarea,
    .dark .fi-select-input {
        color: var(--text) !important;
    }

    .fi-input-wrp input::placeholder,
    .fi-input-wrp textarea::placeholder,
    .dark .fi-input-wrp input::placeholder,
    .dark .fi-input-wrp textarea::placeholder {
        color: var(--muted) !important;
        opacity: 0.7 !important;
    }

    /* 9. BADGES & PILLS */
    .dark .fi-badge-color-primary,
    .dark .fi-badge-color-success {
        background-color: rgba(0, 224, 126, 0.15) !important;
        color: var(--accent) !important;
        border: 1px solid rgba(0, 224, 126, 0.3) !important;
    }

    .dark .fi-badge-color-danger {
        background-color: rgba(242, 123, 123, 0.15) !important;
        color: var(--danger) !important;
        border: 1px solid rgba(242, 123, 123, 0.3) !important;
    }

    .dark .fi-badge-color-warning {
        background-color: rgba(242, 197, 107, 0.15) !important;
        color: var(--warn) !important;
        border: 1px solid rgba(242, 197, 107, 0.3) !important;
    }

    .dark .fi-badge-color-gray {
        background-color: rgba(154, 164, 159, 0.15) !important;
        color: var(--muted) !important;
        border: 1px solid var(--border) !important;
    }

    /* 10. NOC OVERVIEW & NETWORK HEALTH DIAGNOSTICS SYSTEM */
    .fi-wi-network-health,
    div[wire\\:id]:has(.noc-wrapper),
    .fi-wi-widget:has(.noc-wrapper),
    .fi-grid-col:has(.noc-wrapper),
    .fi-sc-dense:has(.noc-wrapper) {
        grid-column: 1 / -1 !important;
        width: 100% !important;
    }

    .noc-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        width: 100%;
        box-sizing: border-box;
    }

    .noc-header-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding-bottom: 0.875rem;
        border-bottom: 1px solid var(--border);
    }

    .noc-title-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .noc-icon-box {
        width: 2.5rem;
        height: 2.5rem;
        min-width: 2.5rem;
        border-radius: 0.75rem;
        background: var(--soft);
        border: 1px solid rgba(0, 224, 126, 0.3);
        color: var(--accent);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .noc-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text);
        margin: 0;
        line-height: 1.25;
    }

    .noc-subtitle {
        font-size: 0.75rem;
        color: var(--muted);
        margin: 0.2rem 0 0 0;
        line-height: 1.4;
    }

    .noc-actions-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .noc-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.85rem;
        border-radius: 0.625rem;
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .noc-btn-primary {
        background: var(--btn) !important;
        color: #ffffff !important;
        font-weight: 800 !important;
        box-shadow: 0 4px 12px rgba(0, 224, 126, 0.25);
    }
    .noc-btn-primary * {
        color: #ffffff !important;
    }
    .noc-btn-primary:hover {
        background: var(--accent2) !important;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(0, 224, 126, 0.4);
    }
    .noc-btn-primary:hover * {
        color: #ffffff !important;
    }

    .noc-btn-secondary {
        background: #06261a !important;
        color: #ffffff !important;
        border: 1px solid rgba(0, 224, 126, 0.3) !important;
    }
    .noc-btn-secondary * {
        color: #ffffff !important;
    }
    .noc-btn-secondary:hover {
        background: #0f3a25 !important;
        border-color: rgba(0, 224, 126, 0.5) !important;
        color: #ffffff !important;
    }

    /* Grids */
    .noc-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        width: 100%;
        box-sizing: border-box;
    }
    @media (min-width: 640px) and (max-width: 1023px) {
        .noc-grid-3 {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
    @media (min-width: 1024px) {
        .noc-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }

    /* Individual NOC Cards */
    .noc-subcard {
        border-radius: 0.875rem;
        padding: 1rem 1.125rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.75rem;
        font-size: 0.75rem;
        border: 1px solid var(--border);
        background: var(--surface2);
        box-sizing: border-box;
    }

    .noc-subcard-alert {
        border-color: rgba(242, 123, 123, 0.4) !important;
        background: rgba(242, 123, 123, 0.08) !important;
    }

    .noc-subcard-success {
        border-color: rgba(0, 224, 126, 0.3) !important;
        background: rgba(15, 58, 37, 0.25) !important;
    }

    .noc-card-header {
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--muted);
    }

    .noc-dot {
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 9999px;
        display: inline-block;
        flex-shrink: 0;
    }
    .noc-dot-primary {
        background-color: var(--accent);
        box-shadow: 0 0 8px rgba(0, 224, 126, 0.6);
    }
    .noc-dot-success {
        background-color: var(--accent);
        box-shadow: 0 0 8px rgba(0, 224, 126, 0.6);
    }
    .noc-dot-danger {
        background-color: var(--danger);
        box-shadow: 0 0 8px rgba(242, 123, 123, 0.6);
    }
    .noc-dot-warning {
        background-color: var(--warn);
        box-shadow: 0 0 8px rgba(242, 197, 107, 0.6);
    }

    .noc-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.35rem 0;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .noc-row + .noc-row {
        border-top: 1px solid var(--border);
    }

    .noc-label {
        color: var(--muted);
    }

    /* Badges */
    .noc-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.55rem;
        border-radius: 0.375rem;
        font-size: 0.6875rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .noc-pill-success {
        background: rgba(0, 224, 126, 0.15);
        color: var(--accent);
        border: 1px solid rgba(0, 224, 126, 0.3);
    }
    .noc-pill-danger {
        background: rgba(242, 123, 123, 0.15);
        color: var(--danger);
        border: 1px solid rgba(242, 123, 123, 0.3);
    }
    .noc-pill-warning {
        background: rgba(242, 197, 107, 0.15);
        color: var(--warn);
        border: 1px solid rgba(242, 197, 107, 0.3);
    }

    /* Outage Item Box */
    .noc-outage-box {
        padding: 0.625rem 0.75rem;
        border-radius: 0.5rem;
        background: var(--surface);
        border: 1px solid rgba(242, 123, 123, 0.4);
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    /* Progress bar */
    .noc-progress-track {
        width: 100%;
        height: 0.5rem;
        border-radius: 9999px;
        background: var(--border);
        overflow: hidden;
    }

    .noc-progress-bar {
        height: 100%;
        border-radius: 9999px;
        background: var(--accent);
        box-shadow: 0 0 10px rgba(0, 224, 126, 0.5);
        transition: width 0.4s ease;
    }

    /* Modern Tabs Bar */
    .noc-tabs-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem;
        border-radius: 0.875rem;
        background: var(--surface);
        border: 1px solid var(--border);
    }
    .noc-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 1.1rem;
        border-radius: 0.625rem;
        font-size: 0.8125rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        color: var(--muted);
        background: transparent;
        text-decoration: none;
    }
    .noc-tab-btn:hover {
        background: var(--surface2);
        color: var(--text);
    }
    .noc-tab-btn.active {
        background: var(--btn) !important;
        color: var(--btnText) !important;
        font-weight: 800 !important;
        box-shadow: 0 4px 14px rgba(0, 224, 126, 0.35) !important;
    }

    /* 11. ULTRA-SMOOTH CUSTOM SCROLLBARS */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: var(--bg);
    }
    ::-webkit-scrollbar-thumb {
        background: var(--border);
        border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: var(--accent);
    }

    /* 12. RESPONSIVE MOBILE POLISH */
    @media (max-width: 640px) {
        .fi-header-heading,
        .fi-page-header-heading {
            font-size: 1.25rem !important;
            line-height: 1.3 !important;
        }

        .fi-header-actions-ctn,
        .fi-page-header-actions {
            width: 100% !important;
        }

        .fi-header .fi-ac,
        .fi-page-header .fi-ac,
        .fi-header-actions-ctn .fi-ac {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 0.45rem !important;
            width: 100% !important;
        }

        .fi-header .fi-ac > :first-child:nth-last-child(odd),
        .fi-page-header .fi-ac > :first-child:nth-last-child(odd),
        .fi-header-actions-ctn .fi-ac > :first-child:nth-last-child(odd) {
            grid-column: 1 / -1 !important;
        }

        .noc-actions-group {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 0.45rem !important;
            width: 100% !important;
        }

        .noc-actions-group > :first-child:nth-last-child(odd) {
            grid-column: 1 / -1 !important;
        }
    }

    /* ==========================================================================
       SETTINGS PAGE QUICK TOOLS HUB
       ========================================================================== */
    .settings-hub-wrapper {
        margin-bottom: 1.5rem !important;
        padding: 1.1rem 1.25rem !important;
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        border-radius: 1rem !important;
    }
    .settings-hub-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 1rem !important;
    }
    .settings-hub-grid {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 0.875rem !important;
    }
    @media (max-width: 1024px) {
        .settings-hub-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
    @media (max-width: 640px) {
        .settings-hub-grid {
            grid-template-columns: 1fr !important;
        }
    }
    .settings-hub-card {
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        padding: 1rem !important;
        background: var(--surface2) !important;
        border: 1px solid var(--border) !important;
        border-radius: 0.75rem !important;
        text-decoration: none !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .settings-hub-card:hover {
        border-color: rgba(0, 224, 126, 0.5) !important;
        background: var(--surface) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 20px -4px rgba(0, 224, 126, 0.12) !important;
    }
    .settings-hub-icon-box {
        width: 2.5rem !important;
        height: 2.5rem !important;
        border-radius: 0.5rem !important;
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: var(--accent) !important;
        transition: all 0.2s ease !important;
    }
    .settings-hub-card:hover .settings-hub-icon-box {
        background: #00e07e !important;
        color: #ffffff !important;
        border-color: #00e07e !important;
    }
    .settings-hub-badge {
        font-size: 0.7rem !important;
        font-weight: 600 !important;
        padding: 0.2rem 0.5rem !important;
        border-radius: 0.375rem !important;
        background: var(--surface) !important;
        color: var(--muted) !important;
        border: 1px solid var(--border) !important;
        transition: color 0.2s ease !important;
    }
    .settings-hub-card:hover .settings-hub-badge {
        color: #00e07e !important;
        border-color: rgba(0, 224, 126, 0.3) !important;
    }
    .settings-hub-title {
        font-size: 0.875rem !important;
        font-weight: 700 !important;
        color: var(--text) !important;
        margin-top: 0.75rem !important;
        margin-bottom: 0.25rem !important;
        transition: color 0.2s ease !important;
    }
    .settings-hub-card:hover .settings-hub-title {
        color: #00e07e !important;
    }
    .settings-hub-desc {
        font-size: 0.75rem !important;
        color: var(--muted) !important;
        line-height: 1.35 !important;
        margin: 0 !important;
    }

    /* ==========================================================================
       ROUTER MONITORING & METRIC GRID SYSTEM (4-COLUMN NOC CARDS)
       ========================================================================== */
    .isp-grid-4 {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 1rem !important;
        margin-bottom: 1.25rem !important;
        width: 100% !important;
    }
    @media (max-width: 1200px) {
        .isp-grid-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
    @media (max-width: 640px) {
        .isp-grid-4 {
            grid-template-columns: 1fr !important;
        }
    }

    .isp-metric-card {
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        border-radius: 1rem !important;
        padding: 1.15rem 1.25rem !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        gap: 0.75rem !important;
        min-height: 142px !important;
        position: relative !important;
        overflow: hidden !important;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s ease, box-shadow 0.2s ease !important;
    }
    .isp-metric-card:hover {
        transform: translateY(-2px) !important;
        border-color: rgba(0, 224, 126, 0.45) !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 0 20px rgba(0, 224, 126, 0.1) !important;
    }

    .noc-card-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
    }
    .noc-card-label-wrap {
        display: flex !important;
        align-items: center !important;
        gap: 0.45rem !important;
    }
    .noc-pulse-dot {
        width: 7px !important;
        height: 7px !important;
        border-radius: 9999px !important;
        display: inline-block !important;
        flex-shrink: 0 !important;
    }
    .noc-card-label {
        font-size: 0.725rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.06em !important;
        color: var(--muted) !important;
    }
    .noc-metric-icon {
        width: 30px !important;
        height: 30px !important;
        border-radius: 8px !important;
        background: var(--surface2) !important;
        border: 1px solid var(--border) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: var(--accent) !important;
        flex-shrink: 0 !important;
    }
    .noc-card-body {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.35rem !important;
    }
    .noc-metric-val {
        font-size: 1.65rem !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em !important;
        line-height: 1.15 !important;
        color: var(--text) !important;
        font-family: 'Plus Jakarta Sans', ui-monospace, monospace !important;
    }
    .noc-track {
        width: 100% !important;
        height: 6px !important;
        border-radius: 9999px !important;
        background: var(--surface2) !important;
        border: 1px solid var(--border) !important;
        overflow: hidden !important;
        margin-top: 0.25rem !important;
    }
    .noc-bar {
        height: 100% !important;
        border-radius: 9999px !important;
        transition: width 0.4s ease !important;
    }
    .noc-card-footer {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        font-size: 0.725rem !important;
        color: var(--muted) !important;
        border-top: 1px solid var(--border) !important;
        padding-top: 0.55rem !important;
        margin-top: 0.2rem !important;
    }
    .noc-footer-item {
        display: flex !important;
        align-items: center !important;
        gap: 0.35rem !important;
    }
    .noc-badge-sub {
        font-size: 0.7rem !important;
        font-weight: 700 !important;
        padding: 0.15rem 0.5rem !important;
        border-radius: 0.375rem !important;
        background: var(--surface2) !important;
        border: 1px solid var(--border) !important;
        color: var(--muted) !important;
    }

    /* ==========================================================================
       ISP CLEAN DATA TABLE (NOC TABLES)
       ========================================================================== */
    .isp-table-clean {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        font-size: 0.8125rem !important;
        text-align: left !important;
    }
    .isp-table-clean thead {
        background: var(--surface2) !important;
    }
    .isp-table-clean th {
        padding: 0.85rem 1rem !important;
        font-size: 0.725rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        color: var(--muted) !important;
        border-bottom: 1px solid var(--border) !important;
        border-top: 1px solid var(--border) !important;
        white-space: nowrap !important;
        line-height: 1.3 !important;
    }
    .isp-table-clean td {
        padding: 0.85rem 1rem !important;
        vertical-align: middle !important;
        border-bottom: 1px solid var(--border) !important;
        color: var(--text) !important;
        line-height: 1.4 !important;
    }
    .isp-table-clean tbody tr {
        background-color: var(--surface) !important;
        transition: background-color 0.15s ease !important;
    }
    .isp-table-clean tbody tr:hover td {
        background-color: var(--surface2) !important;
    }
    .isp-table-clean tbody tr:last-child td {
        border-bottom: none !important;
    }
    /* ==========================================================================
       TOP NAVIGATION LOADING PROGRESS BAR (LIVEWIRE 3 SPA)
       ========================================================================== */
    :root {
        --livewire-progress-bar-color: #00e07e;
    }
    #nprogress .bar,
    #livewire-progress-bar,
    .livewire-progress-bar,
    [data-livewire-progress-bar] {
        background-color: #00e07e !important;
        height: 3px !important;
        box-shadow: 0 0 10px #00e07e, 0 0 5px #00e07e !important;
        z-index: 999999 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
    }

    /* Sidebar Navigation Click Immediate Feedback */
    .dark .fi-sidebar-item a {
        transition: transform 0.08s ease, opacity 0.08s ease !important;
    }
    .dark .fi-sidebar-item a:active {
        opacity: 0.75 !important;
        transform: scale(0.98) !important;
    }

    /* ==========================================================================
       FILAMENT ADMIN LOGIN REDESIGN (CYBER NOC THEME)
       Matches Customer Portal Login (resources/views/customer/login.blade.php)
       ========================================================================== */

    /* 1. Simple Layout Background & Ambient Glow */
    .dark .fi-simple-layout {
        background-color: var(--bg) !important;
        position: relative !important;
        min-height: 100vh !important;
        overflow-x: hidden !important;
    }

    .ambient-glow {
        position: fixed;
        width: 450px;
        height: 450px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(0, 224, 126, 0.08) 0%, transparent 70%);
        filter: blur(80px);
        pointer-events: none;
        z-index: 0;
    }

    /* 2. Simple Main (Card Container) */
    .dark .fi-simple-main {
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        box-shadow: 0 1px 0 #ffffff08 inset, 0 20px 40px -15px rgba(0, 0, 0, 0.7) !important;
        border-radius: 1.25rem !important;
        position: relative !important;
        max-width: 28rem !important;
        padding: 2.25rem 2rem !important;
        z-index: 10 !important;
    }

    /* 3. Floating Mascot NetSpeed Animation */
    @keyframes floatMascot {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-8px) rotate(2deg); }
    }
    .float-anim {
        animation: floatMascot 4s ease-in-out infinite;
    }

    .floating-mascot-login {
        position: absolute;
        top: -3.25rem;
        right: -1rem;
        width: 6.5rem;
        z-index: 30;
        filter: drop-shadow(0 10px 25px rgba(0, 224, 126, 0.35));
        animation: floatMascot 4s ease-in-out infinite;
    }
    @media (min-width: 640px) {
        .floating-mascot-login {
            right: -2rem;
            width: 7.5rem;
        }
    }

    /* 4. Simple Header (Logo, Heading & Subheading) */
    .dark .fi-simple-header {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        text-align: center !important;
        margin-bottom: 1.75rem !important;
        gap: 0.5rem !important;
    }
    .dark .fi-simple-header .fi-logo {
        margin-bottom: 0.5rem !important;
    }
    .dark .fi-simple-header-heading {
        color: #ffffff !important;
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        letter-spacing: -0.025em !important;
        line-height: 1.25 !important;
    }
    .dark .fi-simple-header-subheading {
        color: var(--muted) !important;
        font-size: 0.8125rem !important;
        line-height: 1.4 !important;
        margin-top: 0.15rem !important;
    }

    /* 5. Inputs inside Simple Page */
    .dark .fi-simple-main .fi-input-wrp {
        background-color: var(--surface2) !important;
        border: 1px solid var(--border) !important;
        border-radius: 0.75rem !important;
        box-shadow: none !important;
        transition: all 0.15s ease !important;
    }
    .dark .fi-simple-main .fi-input-wrp:focus-within {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 1px var(--accent), 0 0 15px rgba(0, 224, 126, 0.15) !important;
    }
    .dark .fi-simple-main input {
        color: var(--text) !important;
        font-size: 0.875rem !important;
        padding: 0.75rem 1rem !important;
    }
    .dark .fi-simple-main input::placeholder {
        color: rgba(154, 164, 159, 0.6) !important;
    }

    /* 6. Form Field Labels & Hint Links */
    .dark .fi-simple-main .fi-fo-field-wrp-label label {
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        color: var(--muted) !important;
    }
    .dark .fi-simple-main .fi-link {
        color: var(--accent) !important;
        font-size: 0.75rem !important;
    }
    .dark .fi-simple-main .fi-link:hover {
        color: var(--accent2) !important;
        text-decoration: underline !important;
    }

    /* 7. Checkbox styling */
    .dark .fi-simple-main .fi-checkbox-input {
        border-radius: 0.375rem !important;
        background-color: var(--surface2) !important;
        border: 1px solid var(--border) !important;
        color: var(--accent) !important;
    }
    .dark .fi-simple-main .fi-checkbox-input:checked {
        background-color: var(--accent) !important;
        border-color: var(--accent) !important;
    }

    /* 8. Submit Button */
    .dark .fi-simple-main button[type="submit"] {
        background-color: var(--accent) !important;
        color: var(--ink) !important;
        font-weight: 900 !important;
        font-size: 0.9375rem !important;
        border-radius: 0.75rem !important;
        padding: 0.85rem 1.25rem !important;
        box-shadow: 0 8px 20px -4px rgba(0, 224, 126, 0.3) !important;
        transition: all 0.2s ease !important;
        width: 100% !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        border: none !important;
        cursor: pointer !important;
    }
    .dark .fi-simple-main button[type="submit"]:hover {
        background-color: var(--accent2) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 10px 25px -4px rgba(0, 224, 126, 0.45) !important;
    }
    .dark .fi-simple-main button[type="submit"]:active {
        transform: translateY(0px) !important;
    }

    /* 9. Footer Links & Customer Notice */
    .fi-simple-page-footer {
        margin-top: 1.5rem !important;
        padding-top: 1.25rem !important;
        border-top: 1px solid var(--border) !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 1rem !important;
        align-items: center !important;
        width: 100% !important;
    }

    .fi-login-customer-card {
        width: 100% !important;
        background: rgba(0, 224, 126, 0.04) !important;
        border: 1px solid rgba(0, 224, 126, 0.16) !important;
        border-radius: 0.75rem !important;
        padding: 0.75rem 1rem !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        text-align: center !important;
        gap: 0.5rem !important;
        transition: all 0.2s ease !important;
        box-sizing: border-box !important;
    }

    .fi-login-customer-card:hover {
        background: rgba(0, 224, 126, 0.07) !important;
        border-color: rgba(0, 224, 126, 0.3) !important;
    }

    .fi-login-customer-text {
        font-size: 0.75rem !important;
        color: var(--muted) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.375rem !important;
        line-height: 1.3 !important;
    }

    .fi-login-info-icon {
        width: 0.875rem !important;
        height: 0.875rem !important;
        color: var(--accent) !important;
        flex-shrink: 0 !important;
    }

    .fi-login-customer-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.375rem !important;
        padding: 0.45rem 1.125rem !important;
        background: rgba(0, 224, 126, 0.1) !important;
        border: 1px solid rgba(0, 224, 126, 0.28) !important;
        border-radius: 0.5rem !important;
        color: #00e07e !important;
        font-size: 0.8125rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        transition: all 0.2s ease !important;
        width: 100% !important;
        max-width: 260px !important;
        box-sizing: border-box !important;
    }

    .fi-login-customer-btn:hover {
        background: #00e07e !important;
        color: #ffffff !important;
        border-color: #00e07e !important;
        box-shadow: 0 4px 14px rgba(0, 224, 126, 0.35) !important;
        transform: translateY(-1px) !important;
    }

    .fi-login-customer-btn .fi-login-arrow {
        transition: transform 0.2s ease !important;
        display: inline-block !important;
    }

    .fi-login-customer-btn:hover .fi-login-arrow {
        transform: translateX(3px) !important;
    }

    .fi-login-back-wrapper {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        width: 100% !important;
    }

    .fi-login-back-link {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.375rem !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        color: var(--muted) !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
        padding: 0.25rem 0.5rem !important;
    }

    .fi-login-back-link:hover {
        color: #ffffff !important;
        transform: translateX(-2px) !important;
    }

    .fi-login-back-icon {
        width: 0.875rem !important;
        height: 0.875rem !important;
        transition: transform 0.2s ease !important;
    }

    .fi-login-back-link:hover .fi-login-back-icon {
        transform: translateX(-2px) !important;
    }
</style>
