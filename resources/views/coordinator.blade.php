<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Clawra Coordinator</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    
    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                background-color: #FDFDFC;
                color: #1b1b18;
                margin: 0;
                padding: 0;
            }
            
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 2rem;
            }
            
            .header {
                text-align: center;
                margin-bottom: 2rem;
            }
            
            .header h1 {
                font-size: 2rem;
                font-weight: 600;
                margin-bottom: 0.5rem;
            }
            
            .header p {
                color: #706f6c;
            }
            
            .chat-container {
                background-color: white;
                border: 1px solid #e3e3e0;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                height: 500px;
                display: flex;
                flex-direction: column;
            }
            
            .chat-messages {
                flex: 1;
                padding: 1rem;
                overflow-y: auto;
                border-bottom: 1px solid #e3e3e0;
            }
            
            .message {
                margin-bottom: 1rem;
                padding: 0.75rem;
                border-radius: 6px;
            }
            
            .user-message {
                background-color: #f0f0f0;
                margin-left: 20%;
            }
            
            .assistant-message {
                background-color: #e6f7ff;
                margin-right: 20%;
            }
            
            .message-header {
                font-weight: 600;
                margin-bottom: 0.25rem;
                font-size: 0.875rem;
            }
            
            .message-content {
                font-size: 0.95rem;
            }
            
            .input-container {
                padding: 1rem;
                display: flex;
                gap: 0.5rem;
            }
            
            .input-container input {
                flex: 1;
                padding: 0.75rem;
                border: 1px solid #e3e3e0;
                border-radius: 6px;
                font-family: inherit;
            }
            
            .input-container button {
                padding: 0.75rem 1.5rem;
                background-color: #1b1b18;
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 500;
            }
            
            .input-container button:hover {
                background-color: #333;
            }
            
            .status {
                text-align: center;
                padding: 1rem;
                color: #706f6c;
                font-size: 0.875rem;
            }
        </style>
    @endif
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Clawra Coordinator</h1>
            <p>Interact with the AI orchestration system</p>
        </div>
        
        <div class="chat-container">
            <div class="chat-messages" id="chat-messages">
                <div class="message assistant-message">
                    <div class="message-header">Coordinator</div>
                    <div class="message-content">Hello! I'm the Clawra Coordinator. How can I help you today?</div>
                </div>
            </div>
            
            <div class="input-container">
                <input type="text" id="user-input" placeholder="Type your message here..." />
                <button id="send-button">Send</button>
            </div>
        </div>
        
        <div class="status">
            <p>System Status: <span id="status">Ready</span></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chat-messages');
            const userInput = document.getElementById('user-input');
            const sendButton = document.getElementById('send-button');
            const statusElement = document.getElementById('status');
            
            // Function to add a message to the chat
            function addMessage(role, content) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${role}-message`;
                
                const headerDiv = document.createElement('div');
                headerDiv.className = 'message-header';
                headerDiv.textContent = role === 'user' ? 'You' : 'Coordinator';
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.textContent = content;
                
                messageDiv.appendChild(headerDiv);
                messageDiv.appendChild(contentDiv);
                chatMessages.appendChild(messageDiv);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Function to send message to coordinator
            async function sendMessage() {
                const message = userInput.value.trim();
                if (!message) return;
                
                // Add user message to chat
                addMessage('user', message);
                userInput.value = '';
                
                // Update status
                statusElement.textContent = 'Processing...';
                
                try {
                    const response = await fetch('/coordinator/message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({ message: message })
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    addMessage('assistant', data.response);
                    statusElement.textContent = 'Ready';
                } catch (error) {
                    addMessage('assistant', 'Sorry, I encountered an error processing your request.');
                    statusElement.textContent = 'Error';
                    console.error('Error:', error);
                }
            }
            
            // Event listeners
            sendButton.addEventListener('click', sendMessage);
            
            userInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>