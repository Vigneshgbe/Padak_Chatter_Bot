<?php
// ============================================================
//  Padak Pvt Ltd - AI Chatbot
//  API: Anthropic Claude (claude-sonnet-4-20250514)
//  Stack: PHP + Inline CSS + Inline JS (no frameworks)
// ============================================================

// ---------- CONFIG (edit these) ----------
define('ANTHROPIC_API_KEY', 'sk-ant-YOUR_API_KEY_HERE'); // <-- paste your key
define('COMPANY_NAME',      'Padak Pvt Ltd');
define('SUPPORT_EMAIL',     'support@padak.in');
define('WORKING_HOURS',     'Mon–Sat, 9am–6pm IST');
define('BOT_COLOR',         '#1D9E75');
define('BOT_COLOR_LIGHT',   '#E1F5EE');
define('BOT_COLOR_DARK',    '#0F6E56');

define('SYSTEM_PROMPT', "You are a helpful, friendly AI assistant for " . COMPANY_NAME . ".
Your job is to assist customers professionally and warmly.

Company details:
- Company: " . COMPANY_NAME . "
- Support Email: " . SUPPORT_EMAIL . "
- Working Hours: " . WORKING_HOURS . "
- Location: India

Your responsibilities:
- Answer questions about Padak's products and services
- Help with support queries and FAQs
- Guide users to the right team when needed
- Keep responses concise, clear, and professional

If you don't know a specific company detail, politely say you will connect them with a team member.
Never make up information. Always be warm and solution-focused.");

// ---------- HANDLE AJAX POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    $messages = json_decode($_POST['messages'] ?? '[]', true);
    if (!is_array($messages) || empty($messages)) {
        echo json_encode(['error' => 'No messages provided.']);
        exit;
    }

    // Sanitize messages
    $clean = [];
    foreach ($messages as $m) {
        if (isset($m['role'], $m['content']) && in_array($m['role'], ['user', 'assistant'])) {
            $clean[] = [
                'role'    => $m['role'],
                'content' => substr(strip_tags($m['content']), 0, 2000)
            ];
        }
    }

    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 500,
        'system'     => SYSTEM_PROMPT,
        'messages'   => $clean
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo json_encode(['error' => 'cURL error: ' . $curlErr]);
        exit;
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 || isset($data['error'])) {
        $msg = $data['error']['message'] ?? 'API error (HTTP ' . $httpCode . ')';
        echo json_encode(['error' => $msg]);
        exit;
    }

    $reply = $data['content'][0]['text'] ?? 'Sorry, I could not generate a response.';
    echo json_encode(['reply' => $reply]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= COMPANY_NAME ?> – AI Chatbot</title>

    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f6f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* ===== CHAT CONTAINER ===== */
        #chat-container {
            width: 100%;
            max-width: 480px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            background: #ffffff;
            display: flex;
            flex-direction: column;
            height: 600px;
        }

        /* ===== HEADER ===== */
        #chat-header {
            background: <?= BOT_COLOR ?>;
            color: #fff;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        #chat-header .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255,255,255,0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }
        #chat-header .hinfo p {
            font-size: 15px;
            font-weight: 600;
            line-height: 1.3;
        }
        #chat-header .hinfo span {
            font-size: 12px;
            opacity: 0.85;
        }
        #chat-header .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #A8EDCC;
            display: inline-block;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }

        /* ===== MESSAGES AREA ===== */
        #messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px 14px 8px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #f7f9fb;
            scroll-behavior: smooth;
        }
        #messages::-webkit-scrollbar { width: 4px; }
        #messages::-webkit-scrollbar-thumb { background: #d0d5dd; border-radius: 4px; }

        /* ===== BUBBLES ===== */
        .msg-row {
            display: flex;
            gap: 8px;
            max-width: 88%;
        }
        .msg-row.bot  { align-self: flex-start; }
        .msg-row.user { align-self: flex-end; flex-direction: row-reverse; }

        .bot-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: <?= BOT_COLOR_LIGHT ?>;
            color: <?= BOT_COLOR_DARK ?>;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .bubble {
            padding: 10px 14px;
            font-size: 14px;
            line-height: 1.55;
            word-break: break-word;
        }
        .msg-row.bot .bubble {
            background: #ffffff;
            border: 1px solid #e8ecf0;
            border-radius: 4px 14px 14px 14px;
            color: #1a1a2e;
        }
        .msg-row.user .bubble {
            background: <?= BOT_COLOR ?>;
            color: #ffffff;
            border-radius: 14px 4px 14px 14px;
        }

        /* timestamp */
        .ts {
            font-size: 10px;
            color: #aaa;
            text-align: right;
            margin-top: 3px;
            padding-right: 4px;
        }
        .msg-row.bot .ts { text-align: left; padding-left: 4px; }

        /* ===== TYPING DOTS ===== */
        .typing-dots {
            display: flex;
            gap: 5px;
            align-items: center;
            padding: 4px 2px;
        }
        .typing-dots span {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #aab;
            animation: blink 1.3s infinite;
        }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes blink {
            0%,80%,100% { opacity: 0.2; transform: scale(0.85); }
            40%          { opacity: 1;   transform: scale(1); }
        }

        /* ===== QUICK REPLY BUTTONS ===== */
        #quick-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 8px 14px 4px;
            background: #f7f9fb;
            border-top: 1px solid #edf0f4;
            flex-shrink: 0;
        }
        .qbtn {
            font-size: 12px;
            padding: 5px 12px;
            border-radius: 14px;
            border: 1px solid <?= BOT_COLOR ?>;
            background: <?= BOT_COLOR_LIGHT ?>;
            color: <?= BOT_COLOR_DARK ?>;
            cursor: pointer;
            transition: background 0.15s;
            font-family: inherit;
        }
        .qbtn:hover { background: #9FE1CB; }

        /* ===== INPUT ROW ===== */
        #input-row {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            gap: 8px;
            border-top: 1px solid #edf0f4;
            background: #ffffff;
            flex-shrink: 0;
        }
        #user-input {
            flex: 1;
            border: 1px solid #dde2ea;
            border-radius: 22px;
            padding: 9px 16px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            background: #f7f9fb;
            color: #1a1a2e;
            transition: border-color 0.15s;
        }
        #user-input:focus { border-color: <?= BOT_COLOR ?>; background: #fff; }
        #send-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: <?= BOT_COLOR ?>;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background 0.15s, transform 0.1s;
        }
        #send-btn:hover   { background: <?= BOT_COLOR_DARK ?>; }
        #send-btn:active  { transform: scale(0.94); }
        #send-btn:disabled { opacity: 0.45; cursor: default; transform: none; }
        #send-btn svg { width: 18px; height: 18px; fill: #fff; }

        /* ===== FOOTER ===== */
        #chat-footer {
            text-align: center;
            padding: 6px;
            font-size: 10px;
            color: #aaa;
            background: #fff;
            border-top: 1px solid #f0f0f0;
            flex-shrink: 0;
        }

        /* ===== ERROR BANNER ===== */
        .error-bubble {
            background: #fff0f0;
            border: 1px solid #ffd0d0;
            color: #c0392b;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 13px;
            align-self: center;
            max-width: 90%;
            text-align: center;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 520px) {
            body { padding: 0; align-items: flex-end; }
            #chat-container { border-radius: 16px 16px 0 0; height: 96vh; max-width: 100%; }
        }
    </style>
</head>
<body>

<div id="chat-container">

    <!-- HEADER -->
    <div id="chat-header">
        <div class="avatar">P</div>
        <div class="hinfo">
            <p><?= htmlspecialchars(COMPANY_NAME) ?></p>
            <span><span class="status-dot"></span>Online · AI Assistant</span>
        </div>
    </div>

    <!-- MESSAGES -->
    <div id="messages">
        <div class="msg-row bot">
            <div class="bot-icon">P</div>
            <div>
                <div class="bubble">
                    👋 Hi there! I'm Padak's AI Assistant. How can I help you today?
                    Ask me anything about our services, support, or company!
                </div>
                <div class="ts" id="welcome-ts"></div>
            </div>
        </div>
    </div>

    <!-- QUICK REPLIES -->
    <div id="quick-btns">
        <button class="qbtn" onclick="quickSend('What services does Padak offer?')">Our services</button>
        <button class="qbtn" onclick="quickSend('How do I contact support?')">Contact support</button>
        <button class="qbtn" onclick="quickSend('Tell me about Padak Pvt Ltd')">About us</button>
        <button class="qbtn" onclick="quickSend('What are your working hours?')">Working hours</button>
    </div>

    <!-- INPUT -->
    <div id="input-row">
        <input
            id="user-input"
            type="text"
            placeholder="Type your message..."
            autocomplete="off"
            onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); sendMsg(); }"
        />
        <button id="send-btn" onclick="sendMsg()" title="Send">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
            </svg>
        </button>
    </div>

    <!-- FOOTER -->
    <div id="chat-footer">Powered by Claude AI &nbsp;·&nbsp; <?= htmlspecialchars(COMPANY_NAME) ?></div>

</div>

<script>
    // ============================================================
    //  Padak Chatbot — Frontend Logic
    // ============================================================

    var history = [];   // conversation history [{role, content}]
    var busy    = false;

    // Set welcome timestamp
    document.getElementById('welcome-ts').textContent = getTime();

    function getTime() {
        return new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
    }

    function scrollBottom() {
        var m = document.getElementById('messages');
        m.scrollTop = m.scrollHeight;
    }

    // Add a message bubble to the UI
    function addBubble(role, text) {
        var msgs = document.getElementById('messages');
        var row = document.createElement('div');
        row.className = 'msg-row ' + role;

        if (role === 'bot') {
            var icon = document.createElement('div');
            icon.className = 'bot-icon';
            icon.textContent = 'P';
            row.appendChild(icon);
        }

        var inner = document.createElement('div');

        var bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.textContent = text;
        inner.appendChild(bubble);

        var ts = document.createElement('div');
        ts.className = 'ts';
        ts.textContent = getTime();
        inner.appendChild(ts);

        row.appendChild(inner);
        msgs.appendChild(row);
        scrollBottom();
        return bubble;
    }

    // Show error inline
    function addError(msg) {
        var msgs = document.getElementById('messages');
        var el = document.createElement('div');
        el.className = 'error-bubble';
        el.textContent = '⚠ ' + msg;
        msgs.appendChild(el);
        scrollBottom();
    }

    // Typing indicator
    function showTyping() {
        var msgs = document.getElementById('messages');
        var row = document.createElement('div');
        row.className = 'msg-row bot';
        row.id = 'typing-row';

        var icon = document.createElement('div');
        icon.className = 'bot-icon';
        icon.textContent = 'P';
        row.appendChild(icon);

        var inner = document.createElement('div');
        var bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';
        inner.appendChild(bubble);
        row.appendChild(inner);
        msgs.appendChild(row);
        scrollBottom();
    }

    function removeTyping() {
        var el = document.getElementById('typing-row');
        if (el) el.remove();
    }

    // Send via AJAX to PHP backend
    function sendMsg() {
        if (busy) return;
        var input = document.getElementById('user-input');
        var text  = input.value.trim();
        if (!text) return;

        input.value = '';
        busy = true;
        document.getElementById('send-btn').disabled = true;
        document.getElementById('quick-btns').style.display = 'none';

        // Add user bubble
        addBubble('user', text);
        history.push({ role: 'user', content: text });
        showTyping();

        // AJAX POST
        var fd = new FormData();
        fd.append('ajax', '1');
        fd.append('messages', JSON.stringify(history));

        fetch(window.location.href, {
            method: 'POST',
            body: fd
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            removeTyping();
            if (data.error) {
                addError(data.error);
            } else {
                history.push({ role: 'assistant', content: data.reply });
                addBubble('bot', data.reply);
            }
        })
        .catch(function(err) {
            removeTyping();
            addError('Network error. Please check your connection and try again.');
        })
        .finally(function() {
            busy = false;
            document.getElementById('send-btn').disabled = false;
            document.getElementById('user-input').focus();
        });
    }

    function quickSend(text) {
        document.getElementById('user-input').value = text;
        sendMsg();
    }

    // Auto-focus input
    document.getElementById('user-input').focus();
</script>

</body>
</html>