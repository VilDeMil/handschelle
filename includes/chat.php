<?php
/**
 * Die-Handschelle – Ollama Chat
 *
 * Shortcode: [handschelle-chat]
 *
 * Flow:
 *   1. User enters Ollama endpoint (IP:PORT)
 *   2. JS fetches available models via GET /api/tags
 *   3. User selects a model
 *   4. Chat interface opens; messages streamed via POST /api/chat
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Handschelle_Chat {

    public function __construct() {
        add_shortcode( 'handschelle-chat', array( $this, 'sc_chat' ) );
        add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
        add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
    }

    /* ── Assets ─────────────────────────────────────────────────── */
    public function enqueue() {
        wp_enqueue_style(
            'hs-chat-style',
            HANDSCHELLE_PLUGIN_URL . 'assets/css/handschelle-chat.css',
            array(),
            HANDSCHELLE_VERSION
        );
        wp_enqueue_script(
            'hs-chat-script',
            HANDSCHELLE_PLUGIN_URL . 'assets/js/handschelle-chat.js',
            array(),
            HANDSCHELLE_VERSION,
            true
        );
    }

    public function enqueue_admin( $hook ) {
        if ( strpos( $hook, 'handschelle-chat' ) === false ) return;
        $this->enqueue();
    }

    /* ── Admin-Menü ─────────────────────────────────────────────── */
    public function register_menu() {
        add_submenu_page(
            'handschelle',
            'Ollama Chat',
            '💬 Ollama Chat',
            'manage_options',
            'handschelle-chat',
            array( $this, 'page_chat' )
        );
    }

    public function page_chat() {
        echo '<div class="wrap"><h1>Ollama Chat</h1>';
        echo do_shortcode( '[handschelle-chat]' );
        echo '</div>';
    }

    /* ── Shortcode ──────────────────────────────────────────────── */
    public function sc_chat( $atts ) {
        $atts = shortcode_atts( array(
            'height' => '520px',
        ), $atts, 'handschelle-chat' );

        $height = esc_attr( $atts['height'] );

        ob_start();
        ?>
        <div class="hs-chat" style="--hs-chat-height:<?php echo $height; ?>">

            <!-- Step 1: Endpoint -->
            <div class="hs-chat__step" id="hs-chat-step-endpoint">
                <div class="hs-chat__setup-card">
                    <h2 class="hs-chat__setup-title">Ollama verbinden</h2>
                    <p class="hs-chat__setup-desc">Gib die IP-Adresse und den Port deines Ollama-Servers ein.</p>
                    <div class="hs-chat__input-row">
                        <input
                            type="text"
                            id="hs-chat-endpoint"
                            class="hs-chat__input"
                            placeholder="192.168.1.100:11434"
                            autocomplete="off"
                            spellcheck="false"
                        />
                        <button class="hs-chat__btn hs-chat__btn--primary" id="hs-chat-connect-btn">
                            Verbinden
                        </button>
                    </div>
                    <p class="hs-chat__error" id="hs-chat-endpoint-error" hidden></p>
                </div>
            </div>

            <!-- Step 2: Modell wählen -->
            <div class="hs-chat__step" id="hs-chat-step-model" hidden>
                <div class="hs-chat__setup-card">
                    <h2 class="hs-chat__setup-title">Modell wählen</h2>
                    <p class="hs-chat__setup-desc" id="hs-chat-model-desc"></p>
                    <div class="hs-chat__input-row">
                        <select id="hs-chat-model-select" class="hs-chat__select">
                            <option value="">-- Modell auswählen --</option>
                        </select>
                        <button class="hs-chat__btn hs-chat__btn--primary" id="hs-chat-model-btn">
                            Starten
                        </button>
                    </div>
                    <button class="hs-chat__btn hs-chat__btn--ghost" id="hs-chat-back-btn">
                        ← Zurück
                    </button>
                    <p class="hs-chat__error" id="hs-chat-model-error" hidden></p>
                </div>
            </div>

            <!-- Step 3: Chat -->
            <div class="hs-chat__step" id="hs-chat-step-chat" hidden>
                <div class="hs-chat__header">
                    <span class="hs-chat__header-info">
                        <span class="hs-chat__dot"></span>
                        <span id="hs-chat-active-model"></span>
                    </span>
                    <button class="hs-chat__btn hs-chat__btn--ghost hs-chat__btn--sm" id="hs-chat-reset-btn">
                        ✕ Trennen
                    </button>
                </div>
                <div class="hs-chat__messages" id="hs-chat-messages" aria-live="polite"></div>
                <div class="hs-chat__composer">
                    <textarea
                        id="hs-chat-input"
                        class="hs-chat__textarea"
                        placeholder="Nachricht eingeben… (Enter zum Senden, Shift+Enter für neue Zeile)"
                        rows="1"
                    ></textarea>
                    <button class="hs-chat__btn hs-chat__btn--send" id="hs-chat-send-btn" aria-label="Senden">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }
}

new Handschelle_Chat();
