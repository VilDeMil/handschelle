<?php
/**
 * Die-Handschelle – Ollama Chat
 *
 * Shortcode: [handschelle-chat]
 *
 * Flow:
 *   1. User enters Ollama endpoint (IP:PORT)
 *   2. PHP proxy fetches available models via GET /api/tags
 *   3. User selects a model
 *   4. Chat interface opens; messages streamed via PHP proxy → /api/chat
 *
 * AJAX actions (public):
 *   hs_chat_models  – fetch model list from Ollama
 *   hs_chat_stream  – stream chat response from Ollama (NDJSON passthrough)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Handschelle_Chat {

    public function __construct() {
        add_shortcode( 'handschelle-chat', array( $this, 'sc_chat' ) );
        add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
        add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );

        /* AJAX proxy – accessible for logged-in AND public visitors */
        add_action( 'wp_ajax_hs_chat_models',        array( $this, 'ajax_models' ) );
        add_action( 'wp_ajax_nopriv_hs_chat_models', array( $this, 'ajax_models' ) );
        add_action( 'wp_ajax_hs_chat_stream',        array( $this, 'ajax_chat_stream' ) );
        add_action( 'wp_ajax_nopriv_hs_chat_stream', array( $this, 'ajax_chat_stream' ) );
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
        wp_localize_script( 'hs-chat-script', 'hsChatAjax', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'hs_chat_nonce' ),
        ) );
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

    /* ── AJAX: fetch model list ──────────────────────────────────── */
    public function ajax_models() {
        check_ajax_referer( 'hs_chat_nonce', 'nonce' );

        $endpoint = $this->sanitise_endpoint( isset( $_POST['endpoint'] ) ? $_POST['endpoint'] : '' );
        if ( ! $endpoint ) {
            wp_send_json_error( 'Ungültige Endpoint-URL.' );
        }

        $response = wp_remote_get(
            $endpoint . '/api/tags',
            array( 'timeout' => 10, 'sslverify' => false )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            wp_send_json_error( 'Ollama antwortete mit HTTP ' . $code );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        wp_send_json_success( $body );
    }

    /* ── AJAX: stream chat response ─────────────────────────────── */
    public function ajax_chat_stream() {
        check_ajax_referer( 'hs_chat_nonce', 'nonce' );

        $endpoint = $this->sanitise_endpoint( isset( $_POST['endpoint'] ) ? $_POST['endpoint'] : '' );
        $model    = isset( $_POST['model'] )    ? sanitize_text_field( wp_unslash( $_POST['model'] ) )    : '';
        $raw_msgs = isset( $_POST['messages'] ) ? wp_unslash( $_POST['messages'] ) : '[]';
        $messages = json_decode( $raw_msgs, true );

        if ( ! $endpoint || ! $model || ! is_array( $messages ) ) {
            http_response_code( 400 );
            echo json_encode( array( 'error' => 'Ungültige Parameter.' ) );
            exit;
        }

        /* Sanitise message content */
        $clean = array();
        foreach ( $messages as $msg ) {
            if ( isset( $msg['role'], $msg['content'] ) ) {
                $clean[] = array(
                    'role'    => sanitize_text_field( $msg['role'] ),
                    'content' => sanitize_textarea_field( $msg['content'] ),
                );
            }
        }

        /* Stream headers */
        header( 'Content-Type: application/x-ndjson; charset=utf-8' );
        header( 'X-Accel-Buffering: no' );   /* nginx: disable proxy buffering */
        header( 'Cache-Control: no-cache' );
        header( 'Connection: keep-alive' );

        /* Disable output buffering */
        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        $payload = json_encode( array(
            'model'    => $model,
            'messages' => $clean,
            'stream'   => true,
        ) );

        /* Use cURL for streaming passthrough */
        if ( function_exists( 'curl_init' ) ) {
            $ch = curl_init( $endpoint . '/api/chat' );
            curl_setopt_array( $ch, array(
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => array( 'Content-Type: application/json' ),
                CURLOPT_WRITEFUNCTION  => function ( $ch, $data ) {
                    echo $data;
                    flush();
                    return strlen( $data );
                },
                CURLOPT_TIMEOUT        => 300,
                CURLOPT_SSL_VERIFYPEER => false,
            ) );
            curl_exec( $ch );
            $curl_err = curl_error( $ch );
            curl_close( $ch );
            if ( $curl_err ) {
                echo json_encode( array( 'error' => $curl_err ) ) . "\n";
            }
        } else {
            /* Fallback: wp_remote_post (buffers full response, no streaming) */
            $response = wp_remote_post( $endpoint . '/api/chat', array(
                'body'      => $payload,
                'headers'   => array( 'Content-Type' => 'application/json' ),
                'timeout'   => 120,
                'sslverify' => false,
            ) );
            if ( is_wp_error( $response ) ) {
                echo json_encode( array( 'error' => $response->get_error_message() ) ) . "\n";
            } else {
                echo wp_remote_retrieve_body( $response );
            }
        }

        exit;
    }

    /* ── Helper: sanitise endpoint URL ──────────────────────────── */
    private function sanitise_endpoint( $raw ) {
        $raw = trim( $raw );
        if ( ! preg_match( '#^https?://#i', $raw ) ) {
            $raw = 'http://' . $raw;
        }
        $raw = rtrim( $raw, '/' );
        /* Allow only http/https to an IP or hostname + optional port */
        if ( ! filter_var( $raw, FILTER_VALIDATE_URL ) ) {
            return '';
        }
        $scheme = parse_url( $raw, PHP_URL_SCHEME );
        if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
            return '';
        }
        return esc_url_raw( $raw );
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
