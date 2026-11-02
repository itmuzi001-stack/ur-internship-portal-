<?php
// includes/footer.php
?>
<footer style="background-color: #0F1B2D; color: #F8FAFC; padding: 3rem 0 1.5rem 0; margin-top: auto; font-family: inherit; border-top: 1px solid #1E293B;">
    <div class="container" style="max-width: 1100px; margin: 0 auto; padding: 0 1.5rem;">
        
        <!-- Footer Columns Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2.5rem; margin-bottom: 2.5rem;">
            
            <!-- Column 1: About -->
            <div>
                <h3 style="color: #60A5FA; font-size: 1.1rem; margin-bottom: 1rem; font-weight: 700; letter-spacing: 0.5px;">Internship Portal</h3>
                <p style="color: #94A3B8; font-size: 0.95rem; line-height: 1.6; margin: 0;">
                    Streamlining year 4 student placements across public secondary school locations.
                </p>
            </div>

            <!-- Column 2: Quick Links -->
            <div>
                <h3 style="color: #60A5FA; font-size: 1.1rem; margin-bottom: 1rem; font-weight: 700; letter-spacing: 0.5px;">Quick Links</h3>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.6rem;">
                    <li><a href="dashboard.php" style="color: #CBD5E1; text-decoration: none; font-size: 0.95rem; transition: color 0.2s;">Student Dashboard</a></li>
                    <li><a href="apply.php" style="color: #CBD5E1; text-decoration: none; font-size: 0.95rem; transition: color 0.2s;">Submit Application</a></li>
                    <li><a href="status.php" style="color: #CBD5E1; text-decoration: none; font-size: 0.95rem; transition: color 0.2s;">Check Status</a></li>
                </ul>
            </div>

            <!-- Column 3: Support -->
            <div>
                <h3 style="color: #60A5FA; font-size: 1.1rem; margin-bottom: 1rem; font-weight: 700; letter-spacing: 0.5px;">Support & Guidance</h3>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.6rem;">
                    <li><a href="#" style="color: #CBD5E1; text-decoration: none; font-size: 0.95rem;">Placement Guidelines</a></li>
                    <li><a href="#" style="color: #CBD5E1; text-decoration: none; font-size: 0.95rem;">System Helpdesk</a></li>
                </ul>
            </div>

        </div>

        <!-- Bottom Copyright Bar -->
        <div style="border-top: 1px solid #1E293B; padding-top: 1.5rem; text-align: center; color: #64748B; font-size: 0.9rem;">
            <p style="margin: 0;">&copy; <?= date('Y') ?> Internship Management Portal. All rights reserved.</p>
        </div>
        
    </div>
</footer>

<!-- ================= AI CHAT ASSISTANT WIDGET ================= -->
<div id="ai-chat-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; font-family: 'Inter', Arial, sans-serif;">
    <!-- Chat Toggle Button -->
    <button id="ai-toggle-btn" onclick="toggleAIChat()" style="background-color: #12356B; color: #FFFFFF; border: none; width: 56px; height: 56px; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.2); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.2s;">
        🤖
    </button>

    <!-- Chat Box Container -->
    <div id="ai-chat-box" style="display: none; position: absolute; bottom: 70px; right: 0; width: 360px; max-height: 500px; background: #FFFFFF; border: 1px solid #D9E0EA; border-radius: 12px; box-shadow: 0 10px 25px rgba(15,27,45,0.2); flex-direction: column; overflow: hidden;">
        
        <!-- Header -->
        <div style="background: #12356B; color: #FFFFFF; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.2rem;">✨</span>
                <strong>UR AI Assistant</strong>
            </div>
            <button onclick="toggleAIChat()" style="background: none; border: none; color: #FFFFFF; font-size: 1.2rem; cursor: pointer;">&times;</button>
        </div>

        <!-- Messages Area -->
        <div id="ai-messages" style="flex: 1; padding: 1rem; overflow-y: auto; max-height: 340px; min-height: 250px; display: flex; flex-direction: column; gap: 0.75rem; background: #F3F6FA; font-size: 0.9rem; line-height: 1.5;">
            <div style="background: #FFFFFF; color: #0F1B2D; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid #D9E0EA; align-self: flex-start; max-width: 85%;">
                Hello! I am your University of Rwanda Internship Assistant. Ask me about applications, placements, or required skills!
            </div>
        </div>

        <!-- Input Area -->
        <div style="padding: 0.75rem; background: #FFFFFF; border-top: 1px solid #D9E0EA; display: flex; gap: 0.5rem;">
            <input type="text" id="ai-input" placeholder="Ask about placements, deadlines..." onkeypress="handleAIKeyPress(event)" style="flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #D9E0EA; border-radius: 6px; font-size: 0.9rem; outline: none;">
            <button id="ai-send-btn" onclick="sendAIMessage()" style="background: #E8A317; color: #0F1B2D; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer;">Send</button>
        </div>
    </div>
</div>

<script>
function toggleAIChat() {
    const box = document.getElementById('ai-chat-box');
    box.style.display = box.style.display === 'flex' ? 'none' : 'flex';
}

function handleAIKeyPress(e) {
    if (e.key === 'Enter') {
        sendAIMessage();
    }
}

async function sendAIMessage() {
    const input = document.getElementById('ai-input');
    const sendBtn = document.getElementById('ai-send-btn');
    const messages = document.getElementById('ai-messages');
    const text = input.value.trim();

    if (!text) return;

    // Disable input while processing
    input.disabled = true;
    sendBtn.disabled = true;

    // Append User Message
    messages.innerHTML += `<div style="background: #12356B; color: #FFFFFF; padding: 0.75rem 1rem; border-radius: 8px; align-self: flex-end; max-width: 85%; word-wrap: break-word;">${escapeHtml(text)}</div>`;
    input.value = '';
    messages.scrollTop = messages.scrollHeight;

    // Append Loading Indicator with unique ID
    const loadingId = 'loading-' + Date.now();
    const loadingElement = document.createElement('div');
    loadingElement.id = loadingId;
    loadingElement.style.cssText = 'background: #FFFFFF; color: #4A5668; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid #D9E0EA; align-self: flex-start;';
    loadingElement.innerText = 'Thinking...';
    messages.appendChild(loadingElement);
    messages.scrollTop = messages.scrollHeight;

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000); // 15s timeout

        const response = await fetch('/ur_internship/api/ai-chat-handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text }),
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        // Remove loading state safely
        const loader = document.getElementById(loadingId);
        if (loader) loader.remove();

        const data = await response.json();

        if (data.reply) {
            const formattedReply = formatReplyText(data.reply);
            messages.innerHTML += `<div style="background: #FFFFFF; color: #0F1B2D; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid #D9E0EA; align-self: flex-start; max-width: 85%; word-wrap: break-word;">${formattedReply}</div>`;
        } else {
            messages.innerHTML += `<div style="background: #fde8e8; color: #a80000; padding: 0.75rem 1rem; border-radius: 8px; align-self: flex-start;">An error occurred while loading the response.</div>`;
        }
    } catch (err) {
        const loader = document.getElementById(loadingId);
        if (loader) loader.remove();

        if (err.name === 'AbortError') {
            messages.innerHTML += `<div style="background: #fde8e8; color: #a80000; padding: 0.75rem 1rem; border-radius: 8px; align-self: flex-start;">Request timed out. Please try again.</div>`;
        } else {
            messages.innerHTML += `<div style="background: #fde8e8; color: #a80000; padding: 0.75rem 1rem; border-radius: 8px; align-self: flex-start;">Connection error. Check your server connection.</div>`;
        }
    } finally {
        input.disabled = false;
        sendBtn.disabled = false;
        input.focus();
        messages.scrollTop = messages.scrollHeight;
    }
}

function escapeHtml(text) {
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

function formatReplyText(text) {
    let clean = escapeHtml(text);
    // Convert newlines to break tags
    clean = clean.replace(/\n/g, '<br>');
    // Bold markdown formatting (**text**)
    clean = clean.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    return clean;
}
</script>
<!-- ================= END AI CHAT ASSISTANT WIDGET ================= -->

</body>
</html>