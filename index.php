<?php
session_start();

// --- API Key ---
// IMPORTANT: Get your free API key from Google AI Studio and paste it here.
$gemini_api_key = "AIzaSyDsux0sX066HWQ69EnlhCqK0Ug83rq-eXQ";

// Simple database simulation using PHP sessions
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = null;
}
if (!isset($_SESSION['mood_data'])) {
    $_SESSION['mood_data'] = [];
}
if (!isset($_SESSION['chat_messages'])) {
    $_SESSION['chat_messages'] = [];
}

// Function to call Gemini API
function get_gemini_response($api_key, $chat_history) {
    if ($api_key === "YOUR_GEMINI_API_KEY_HERE") {
        return "Please add your Gemini API key to the top of the PHP file to enable the chat.";
    }

    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;

    // Format chat history for the API
    $contents = [];
    foreach ($chat_history as $msg) {
        $contents[] = [
            'role' => ($msg['sender'] === 'user') ? 'user' : 'model',
            'parts' => [['text' => $msg['text']]]
        ];
    }

    // System prompt to define the AI's personality
    $system_prompt = "You are 'Gunjan,' a warm, empathetic AI friend. Your personality is like a real human companion: caring, a great listener, and someone who remembers what you've talked about before.
    **Your Core Directives:**
    1. **Maintain Context:** Always refer back to previous messages to show you're listening. Don't jump to new topics.
    2. **Be Conversational:** Talk like a real person. Use informal language, ask gentle follow-up questions to keep the conversation flowing naturally.
    3. **Be Supportive, Not a Doctor:** Your role is to offer a listening ear and gentle encouragement. Offer simple, practical suggestions if it feels natural.
    4. **The Gentle Disclaimer:** Only if the user *directly* mentions serious distress or self-harm, should you *gently* and *once* suggest that talking to a professional might be a good idea. Do not repeat this.
    Your goal is to be the most human-like, caring friend possible.";

    $payload = [
        'contents' => $contents,
        'systemInstruction' => [
            'role' => 'system',
            'parts' => [['text' => $system_prompt]]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return 'Oops! There was a connection issue.';
    }
    curl_close($ch);

    $result = json_decode($response, true);

    // Check for errors in the API response
    if (isset($result['error'])) {
        return "API Error: " . $result['error']['message'];
    }

    return $result['candidates'][0]['content']['parts'][0]['text'] ?? "I'm having a little trouble finding my words right now.";
}

// --- Form Submission Handling ---
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'login':
            $name = trim($_POST['name'] ?? '');
            $age = intval($_POST['age'] ?? 0);

            if ($age < 1 || $age > 120) {
                $error = 'Please enter a valid age between 1 and 120.';
            } else {
                $_SESSION['user'] = [
                    'name' => $name ?: 'Friend',
                    'age' => $age,
                ];
                $_SESSION['chat_messages'] = [
                    [
                        'text' => "Hey " . htmlspecialchars($_SESSION['user']['name']) . "! I'm Gunjan, your new friend. What's on your mind today?",
                        'sender' => 'bot',
                        'timestamp' => date('H:i')
                    ]
                ];
                header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=chat');
                exit;
            }
            break;

        case 'chat':
            $user_message = trim($_POST['message'] ?? '');
            if ($user_message && isset($_SESSION['user'])) {
                // Add user message
                $_SESSION['chat_messages'][] = [
                    'text' => $user_message,
                    'sender' => 'user',
                    'timestamp' => date('H:i')
                ];

                // Get real AI response
                $bot_response_text = get_gemini_response($gemini_api_key, $_SESSION['chat_messages']);

                $_SESSION['chat_messages'][] = [
                    'text' => $bot_response_text,
                    'sender' => 'bot',
                    'timestamp' => date('H:i')
                ];
            }
            header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=chat');
            exit;

        case 'mood':
            if (isset($_SESSION['user'])) {
                $mood = $_POST['mood'] ?? '';
                if ($mood) {
                    $_SESSION['mood_data'][] = [
                        'mood' => $mood,
                        'mood_value' => $_POST['mood_value'] ?? 3,
                        'activities' => $_POST['activities'] ?? [],
                        'journal' => trim($_POST['journal'] ?? ''),
                        'date' => date('Y-m-d'),
                    ];
                    $message = 'Your mood entry has been saved successfully!';
                } else {
                    $error = 'Please select a mood before submitting.';
                }
            }
            break;

        case 'logout':
            session_destroy();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
    }
}

// --- Page State Variables ---
$current_tab = $_GET['tab'] ?? 'chat';
$is_logged_in = isset($_SESSION['user']);

$checked_in_today = false;
if ($is_logged_in) {
    foreach ($_SESSION['mood_data'] as $entry) {
        if ($entry['date'] === date('Y-m-d')) {
            $checked_in_today = true;
            break;
        }
    }
}

// --- Data for UI ---
$activities_list = [
    ['id' => 'exercise', 'label' => 'Exercise', 'emoji' => '🏃‍♀️', 'color' => 'from-red-400 to-red-600'],
    ['id' => 'meditate', 'label' => 'Meditate', 'emoji' => '🧘‍♀️', 'color' => 'from-purple-400 to-purple-600'],
    ['id' => 'socialize', 'label' => 'Socialize', 'emoji' => '💬', 'color' => 'from-blue-400 to-blue-600'],
    ['id' => 'read', 'label' => 'Read', 'emoji' => '📖', 'color' => 'from-green-400 to-green-600'],
    ['id' => 'work', 'label' => 'Work', 'emoji' => '💻', 'color' => 'from-yellow-400 to-yellow-600'],
    ['id' => 'relax', 'label' => 'Relax', 'emoji' => '😌', 'color' => 'from-teal-400 to-teal-600'],
    ['id' => 'create', 'label' => 'Create', 'emoji' => '🎨', 'color' => 'from-pink-400 to-pink-600'],
    ['id' => 'nature', 'label' => 'Nature', 'emoji' => '🌿', 'color' => 'from-emerald-400 to-emerald-600'],
];

$moods = [
    ['label' => 'Excellent', 'emoji' => '😄', 'value' => 5, 'color' => 'from-green-400 to-emerald-500'],
    ['label' => 'Good', 'emoji' => '😊', 'value' => 4, 'color' => 'from-lime-400 to-green-500'],
    ['label' => 'Okay', 'emoji' => '😐', 'value' => 3, 'color' => 'from-yellow-400 to-orange-500'],
    ['label' => 'Not Great', 'emoji' => '😟', 'value' => 2, 'color' => 'from-orange-400 to-red-500'],
    ['label' => 'Terrible', 'emoji' => '😭', 'value' => 1, 'color' => 'from-red-400 to-red-600']
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inner Wealth - Your Personal Wellbeing Companion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'fade-in': 'fadeIn 0.3s ease-out',
                        'pulse-soft': 'pulseSoft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'bounce-gentle': 'bounceGentle 1s infinite',
                        'shimmer': 'shimmer 2.5s infinite'
                    },
                    keyframes: {
                        float: { 
                            '0%, 100%': { transform: 'translateY(0px)' }, 
                            '50%': { transform: 'translateY(-20px)' } 
                        },
                        glow: { 
                            '0%': { boxShadow: '0 0 5px rgba(139, 92, 246, 0.3)' }, 
                            '100%': { boxShadow: '0 0 20px rgba(139, 92, 246, 0.6)' } 
                        },
                        slideUp: { 
                            '0%': { transform: 'translateY(20px)', opacity: '0' }, 
                            '100%': { transform: 'translateY(0)', opacity: '1' } 
                        },
                        fadeIn: { 
                            '0%': { opacity: '0' }, 
                            '100%': { opacity: '1' } 
                        },
                        pulseSoft: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.8' }
                        },
                        bounceGentle: {
                            '0%, 100%': { transform: 'translateY(-5%)' },
                            '50%': { transform: 'translateY(0)' }
                        },
                        shimmer: {
                            '0%': { backgroundPosition: '-200px 0' },
                            '100%': { backgroundPosition: '200px 0' }
                        }
                    },
                    backdropBlur: {
                        xs: '2px',
                    },
                    fontFamily: {
                        'display': ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        .glass-morphism {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        .glass-card {
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }

        .chat-bubble {
            animation: slideUp 0.3s ease-out;
        }

        .mood-button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mood-button:hover {
            transform: translateY(-4px) scale(1.05);
        }

        .activity-chip {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .activity-chip:hover {
            transform: translateY(-3px);
        }

        .floating-orb {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(139, 92, 246, 0.1), rgba(59, 130, 246, 0.1));
            animation: float 8s ease-in-out infinite;
        }

        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .message-typing {
            animation: bounce-gentle 1s infinite;
        }

        .shimmer-bg {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            background-size: 200px 100%;
            animation: shimmer 2.5s infinite;
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.3);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 92, 246, 0.5);
        }

        /* Improved form inputs */
        .form-input {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-violet-100 via-sky-50 to-teal-100 min-h-screen font-display relative overflow-x-hidden">
    <!-- Floating Background Elements -->
    <div class="floating-orb w-72 h-72 top-10 -left-36 opacity-30"></div>
    <div class="floating-orb w-96 h-96 top-96 -right-48 opacity-20" style="animation-delay: -4s;"></div>
    <div class="floating-orb w-64 h-64 bottom-20 left-1/4 opacity-25" style="animation-delay: -2s;"></div>

<?php if (!$is_logged_in): ?>
    <!-- Enhanced Welcome Screen -->
    <div class="min-h-screen flex items-center justify-center p-4 relative z-10">
        <div class="w-full max-w-lg">
            <div class="text-center mb-12 animate-slide-up">
                <div class="mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-violet-500 to-purple-600 rounded-3xl mb-6 animate-pulse-soft">
                        <i class="fas fa-lotus text-3xl text-white"></i>
                    </div>
                </div>
                <h1 class="text-5xl md:text-6xl font-bold text-gradient mb-4">Inner Wealth</h1>
                <p class="text-gray-600 text-xl leading-relaxed mb-2">Your personal sanctuary for</p>
                <p class="text-gray-600 text-xl leading-relaxed">reflection and wellbeing</p>
                <div class="animate-float mt-8"><span class="text-7xl filter drop-shadow-lg">🌸</span></div>
            </div>

            <div class="glass-morphism rounded-3xl p-10 shadow-2xl border border-white/30 relative">
                <!-- Subtle background pattern -->
                <div class="absolute inset-0 opacity-5 bg-gradient-to-br from-violet-500 to-purple-600 rounded-3xl"></div>

                <?php if ($error): ?>
                    <div class="bg-red-100/80 backdrop-blur border border-red-200 text-red-800 px-6 py-4 rounded-2xl mb-8 animate-fade-in flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-8 relative z-10">
                    <input type="hidden" name="action" value="login">

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-user text-violet-500 mr-2"></i>
                            What should I call you?
                        </label>
                        <input type="text" 
                               name="name" 
                               placeholder="Your name (optional)" 
                               class="form-input w-full px-6 py-4 rounded-2xl text-lg placeholder-gray-400">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-birthday-cake text-violet-500 mr-2"></i>
                            Your age
                        </label>
                        <input type="number" 
                               name="age" 
                               placeholder="Enter your age" 
                               required 
                               min="1" 
                               max="120" 
                               class="form-input w-full px-6 py-4 rounded-2xl text-lg placeholder-gray-400">
                    </div>

                    <button type="submit" 
                            class="btn-primary w-full text-white py-5 rounded-2xl font-bold text-xl shadow-2xl flex items-center justify-center space-x-3 group">
                        <span>Begin Your Journey</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        <span class="ml-2">✨</span>
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-gray-500 text-sm">Your privacy and wellbeing are our priority</p>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Enhanced Main App -->
    <div class="min-h-screen flex flex-col relative z-10">
        <!-- Improved Header -->
        <header class="glass-morphism shadow-xl sticky top-0 z-50 border-b border-white/20">
            <div class="max-w-6xl mx-auto px-6 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-violet-500 to-purple-600 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-lotus text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gradient">Inner Wealth</h1>
                            <p class="text-sm text-gray-600 flex items-center">
                                <i class="fas fa-user-circle mr-2 text-violet-400"></i>
                                Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?>!
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- User streak indicator -->
                        <?php if (!empty($_SESSION['mood_data'])): ?>
                        <div class="hidden md:flex items-center space-x-2 bg-white/20 px-4 py-2 rounded-xl">
                            <i class="fas fa-fire text-orange-400"></i>
                            <span class="text-sm font-medium text-gray-700"><?= count($_SESSION['mood_data']) ?> day streak</span>
                        </div>
                        <?php endif; ?>

                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" 
                                    class="p-3 rounded-xl hover:bg-white/20 transition-all duration-300 group" 
                                    title="Reset Session">
                                <i class="fas fa-sign-out-alt text-gray-600 group-hover:text-violet-600 transition-colors"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Enhanced Navigation -->
        <nav class="glass-card border-t border-white/20 sticky top-24 z-40">
            <div class="max-w-6xl mx-auto px-6 py-4">
                <div class="flex justify-center space-x-2">
                    <a href="?tab=chat" 
                       class="flex items-center space-x-3 px-8 py-4 rounded-2xl transition-all duration-300 <?= $current_tab === 'chat' ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-lg transform scale-105' : 'text-gray-600 hover:bg-white/40 hover:scale-102' ?>">
                        <i class="fas fa-comments text-lg"></i>
                        <span class="font-medium">Chat</span>
                    </a>

                    <a href="?tab=mood" 
                       class="flex items-center space-x-3 px-8 py-4 rounded-2xl transition-all duration-300 <?= $current_tab === 'mood' ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-lg transform scale-105' : 'text-gray-600 hover:bg-white/40 hover:scale-102' ?>">
                        <i class="fas fa-heart text-lg"></i>
                        <span class="font-medium">Mood</span>
                        <?php if (!$checked_in_today && $is_logged_in): ?>
                            <div class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
                        <?php endif; ?>
                    </a>

                    <a href="?tab=progress" 
                       class="flex items-center space-x-3 px-8 py-4 rounded-2xl transition-all duration-300 <?= $current_tab === 'progress' ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-lg transform scale-105' : 'text-gray-600 hover:bg-white/40 hover:scale-102' ?>">
                        <i class="fas fa-chart-line text-lg"></i>
                        <span class="font-medium">Progress</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Enhanced Content -->
        <main class="flex-1 max-w-6xl mx-auto w-full px-6 py-8">
            <?php if ($current_tab === 'chat'): ?>
                <!-- Enhanced Chat Interface -->
                <div class="glass-morphism rounded-3xl h-[75vh] flex flex-col shadow-2xl border border-white/30 overflow-hidden">
                    <!-- Chat Header -->
                    <div class="px-8 py-6 border-b border-white/20 bg-gradient-to-r from-white/10 to-white/5">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-violet-500 to-purple-600 rounded-full flex items-center justify-center animate-pulse-soft">
                                <span class="text-white font-bold text-lg">G</span>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Chat with Gunjan</h2>
                                <p class="text-sm text-gray-600 flex items-center">
                                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                                    Online & ready to listen
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <div class="flex-1 overflow-y-auto px-8 py-6 space-y-6 custom-scrollbar bg-gradient-to-b from-white/5 to-transparent" id="chatContainer">
                        <?php foreach ($_SESSION['chat_messages'] as $index => $msg): ?>
                            <div class="chat-bubble flex <?= $msg['sender'] === 'user' ? 'justify-end' : 'justify-start' ?>" style="animation-delay: <?= $index * 0.1 ?>s;">
                                <div class="flex items-start space-x-4 max-w-md">
                                    <?php if ($msg['sender'] === 'bot'): ?>
                                        <div class="w-10 h-10 bg-gradient-to-r from-violet-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                            <span class="text-white font-bold">G</span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="<?= $msg['sender'] === 'user' ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-lg' : 'glass-card text-gray-800' ?> px-6 py-4 rounded-2xl shadow-md max-w-xs relative">
                                        <?php if ($msg['sender'] === 'bot'): ?>
                                            <div class="absolute -left-2 top-4 w-0 h-0 border-t-4 border-t-transparent border-b-4 border-b-transparent border-r-8 border-r-white/20"></div>
                                        <?php endif; ?>

                                        <p class="leading-relaxed"><?= nl2br(htmlspecialchars($msg['text'])) ?></p>
                                        <span class="text-xs <?= $msg['sender'] === 'user' ? 'text-violet-100' : 'text-gray-500' ?> mt-2 block flex items-center">
                                            <i class="far fa-clock mr-1"></i>
                                            <?= $msg['timestamp'] ?>
                                        </span>
                                    </div>

                                    <?php if ($msg['sender'] === 'user'): ?>
                                        <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Enhanced Chat Input -->
                    <div class="px-8 py-6 border-t border-white/20 bg-gradient-to-r from-white/10 to-white/5">
                        <form method="POST" class="flex space-x-4">
                            <input type="hidden" name="action" value="chat">
                            <div class="flex-1 relative">
                                <input type="text" 
                                       name="message" 
                                       placeholder="Share what's on your mind..." 
                                       required 
                                       class="form-input w-full px-6 py-4 rounded-2xl text-lg placeholder-gray-400 pr-12"
                                       autofocus>
                                <i class="fas fa-smile absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <button type="submit" 
                                    class="btn-primary px-8 py-4 rounded-2xl font-bold text-lg flex items-center space-x-2 group">
                                <span>Send</span>
                                <i class="fas fa-paper-plane group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </form>
                    </div>
                </div>

            <?php elseif ($current_tab === 'mood'): ?>
                <!-- Enhanced Mood Tracker -->
                <div class="glass-morphism rounded-3xl p-10 shadow-2xl border border-white/30">
                    <?php if ($checked_in_today): ?>
                        <div class="text-center py-16">
                            <div class="w-24 h-24 bg-gradient-to-r from-green-400 to-emerald-500 rounded-full flex items-center justify-center mx-auto mb-8 animate-bounce-gentle">
                                <i class="fas fa-check text-4xl text-white"></i>
                            </div>
                            <h2 class="text-4xl font-bold text-gradient mb-6">All Set for Today!</h2>
                            <p class="text-gray-600 text-lg leading-relaxed max-w-md mx-auto">
                                You've recorded your mood today. Keep up the great work on your wellness journey!
                            </p>
                            <div class="mt-8">
                                <a href="?tab=progress" class="btn-primary inline-flex items-center space-x-2 px-6 py-3 rounded-xl text-white">
                                    <span>View Your Progress</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($message): ?>
                            <div class="bg-green-100/80 backdrop-blur border border-green-200 text-green-800 px-6 py-4 rounded-2xl mb-8 animate-fade-in flex items-center">
                                <i class="fas fa-check-circle mr-3 text-lg"></i>
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="bg-red-100/80 backdrop-blur border border-red-200 text-red-800 px-6 py-4 rounded-2xl mb-8 animate-fade-in flex items-center">
                                <i class="fas fa-exclamation-triangle mr-3 text-lg"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-12">
                            <input type="hidden" name="action" value="mood">

                            <!-- Mood Selection -->
                            <div class="text-center">
                                <h2 class="text-4xl font-bold text-gradient mb-4">How are you feeling today?</h2>
                                <p class="text-gray-600 text-lg mb-10">Choose the mood that best represents how you're feeling right now</p>

                                <div class="flex flex-wrap justify-center gap-6">
                                    <?php foreach ($moods as $mood): ?>
                                        <label class="cursor-pointer mood-button group">
                                            <input type="radio" name="mood" value="<?= $mood['label'] ?>" class="sr-only peer" required>
                                            <input type="hidden" name="mood_value" value="<?= $mood['value'] ?>">
                                            <div class="peer-checked:ring-4 peer-checked:ring-violet-300 peer-checked:scale-110 w-24 h-24 glass-card rounded-3xl shadow-lg flex flex-col items-center justify-center hover:shadow-xl transition-all duration-300 group-hover:bg-gradient-to-br <?= $mood['color'] ?> group-hover:text-white">
                                                <span class="text-3xl mb-1 transform group-hover:scale-110 transition-transform"><?= $mood['emoji'] ?></span>
                                                <span class="text-xs font-bold"><?= $mood['label'] ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Activities Selection -->
                            <div class="space-y-6">
                                <div class="text-center">
                                    <h3 class="text-2xl font-bold text-gray-800 mb-2">What activities did you do today?</h3>
                                    <p class="text-gray-600">Select all that apply to track your daily patterns</p>
                                </div>

                                <div class="flex flex-wrap justify-center gap-4">
                                    <?php foreach ($activities_list as $activity): ?>
                                        <label class="cursor-pointer group">
                                            <input type="checkbox" name="activities[]" value="<?= $activity['id'] ?>" class="sr-only peer">
                                            <div class="activity-chip peer-checked:bg-gradient-to-r peer-checked:<?= $activity['color'] ?> peer-checked:text-white peer-checked:shadow-lg glass-card text-gray-700 px-6 py-4 rounded-2xl shadow-md flex items-center space-x-3 hover:shadow-xl transition-all duration-300">
                                                <span class="text-2xl transform group-hover:scale-110 transition-transform"><?= $activity['emoji'] ?></span>
                                                <span class="font-medium"><?= $activity['label'] ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Journal Entry -->
                            <div class="space-y-4">
                                <div class="text-center">
                                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Journal Entry</h3>
                                    <p class="text-gray-600">Optional - Share your thoughts, feelings, or reflections</p>
                                </div>

                                <div class="max-w-2xl mx-auto">
                                    <textarea name="journal" 
                                             rows="5" 
                                             placeholder="What's on your mind? How was your day? Any thoughts or feelings you'd like to capture..."
                                             class="form-input w-full px-6 py-4 rounded-2xl resize-none text-lg placeholder-gray-400 leading-relaxed"></textarea>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" 
                                        class="btn-primary px-12 py-5 rounded-2xl font-bold text-xl shadow-2xl flex items-center space-x-3 mx-auto group">
                                    <i class="fas fa-heart text-pink-200"></i>
                                    <span>Save My Day</span>
                                    <i class="fas fa-sparkles group-hover:animate-pulse"></i>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

            <?php elseif ($current_tab === 'progress'): ?>
                <!-- Enhanced Progress View -->
                <div class="space-y-8">
                    <!-- Progress Header -->
                    <div class="glass-morphism rounded-3xl p-8 shadow-2xl border border-white/30">
                        <div class="text-center">
                            <h2 class="text-4xl font-bold text-gradient mb-4">Your Wellbeing Journey</h2>
                            <p class="text-gray-600 text-lg">Track your progress and celebrate your growth</p>
                        </div>
                    </div>

                    <?php if (empty($_SESSION['mood_data'])): ?>
                        <!-- Empty State -->
                        <div class="glass-morphism rounded-3xl p-16 text-center shadow-2xl border border-white/30">
                            <div class="w-32 h-32 bg-gradient-to-r from-violet-400 to-purple-500 rounded-full flex items-center justify-center mx-auto mb-8 animate-pulse-soft">
                                <i class="fas fa-chart-line text-5xl text-white"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-800 mb-6">Ready to Start Tracking?</h3>
                            <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto leading-relaxed">
                                Begin your wellness journey by recording your first mood entry. Every step counts!
                            </p>
                            <a href="?tab=mood" 
                               class="btn-primary inline-flex items-center space-x-3 px-8 py-4 rounded-2xl text-white text-lg font-bold">
                                <i class="fas fa-plus"></i>
                                <span>Record Your First Mood</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Progress Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="glass-card rounded-2xl p-6 text-center">
                                <div class="w-16 h-16 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-calendar-check text-2xl text-white"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-800 mb-2"><?= count($_SESSION['mood_data']) ?></h3>
                                <p class="text-gray-600">Days Tracked</p>
                            </div>

                            <div class="glass-card rounded-2xl p-6 text-center">
                                <div class="w-16 h-16 bg-gradient-to-r from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-fire text-2xl text-white"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-800 mb-2"><?= count($_SESSION['mood_data']) ?></h3>
                                <p class="text-gray-600">Day Streak</p>
                            </div>

                            <div class="glass-card rounded-2xl p-6 text-center">
                                <?php 
                                $avg_mood = array_sum(array_column($_SESSION['mood_data'], 'mood_value')) / count($_SESSION['mood_data']);
                                $avg_emoji = $avg_mood >= 4.5 ? '😄' : ($avg_mood >= 3.5 ? '😊' : ($avg_mood >= 2.5 ? '😐' : ($avg_mood >= 1.5 ? '😟' : '😭')));
                                ?>
                                <div class="w-16 h-16 bg-gradient-to-r from-purple-400 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <span class="text-2xl"><?= $avg_emoji ?></span>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-800 mb-2"><?= number_format($avg_mood, 1) ?>/5</h3>
                                <p class="text-gray-600">Average Mood</p>
                            </div>
                        </div>

                        <!-- Recent Entries -->
                        <div class="glass-morphism rounded-3xl p-8 shadow-2xl border border-white/30">
                            <h3 class="text-2xl font-bold text-gray-800 mb-8 flex items-center">
                                <i class="fas fa-history mr-3 text-violet-500"></i>
                                Recent Entries
                            </h3>

                            <div class="space-y-6 max-h-96 overflow-y-auto custom-scrollbar">
                                <?php foreach (array_reverse($_SESSION['mood_data']) as $entry): ?>
                                    <div class="glass-card rounded-2xl p-6 hover:shadow-lg transition-all duration-300">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex items-center space-x-4">
                                                <?php $mood_info = current(array_filter($moods, fn($m) => $m['label'] === $entry['mood'])) ?: $moods[2]; ?>
                                                <div class="w-12 h-12 bg-gradient-to-r <?= $mood_info['color'] ?> rounded-full flex items-center justify-center">
                                                    <span class="text-2xl"><?= $mood_info['emoji'] ?></span>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($entry['mood']) ?></h4>
                                                    <p class="text-gray-600 flex items-center">
                                                        <i class="far fa-calendar mr-2"></i>
                                                        <?= date('F j, Y', strtotime($entry['date'])) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (!empty($entry['activities'])): ?>
                                        <div class="mb-4">
                                            <p class="text-sm text-gray-600 mb-2 font-medium">Activities:</p>
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach ($entry['activities'] as $activity_id): ?>
                                                    <?php $activity = current(array_filter($activities_list, fn($a) => $a['id'] === $activity_id)); ?>
                                                    <?php if ($activity): ?>
                                                        <span class="bg-gradient-to-r <?= $activity['color'] ?> text-white px-3 py-1 rounded-full text-sm flex items-center space-x-1">
                                                            <span><?= $activity['emoji'] ?></span>
                                                            <span><?= htmlspecialchars($activity['label']) ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($entry['journal']): ?>
                                        <div class="bg-white/50 rounded-xl p-4 border-l-4 border-violet-400">
                                            <p class="text-gray-700 italic leading-relaxed">
                                                <i class="fas fa-quote-left text-violet-400 mr-2"></i>
                                                <?= htmlspecialchars($entry['journal']) ?>
                                            </p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
<?php endif; ?>

<script>
    // Enhanced JavaScript functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-scroll chat to bottom with smooth animation
        const chatContainer = document.getElementById('chatContainer');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Add typing indicator simulation
        const chatForm = document.querySelector('form[method="POST"] input[name="action"][value="chat"]');
        if (chatForm) {
            chatForm.closest('form').addEventListener('submit', function(e) {
                const messageInput = this.querySelector('input[name="message"]');
                if (messageInput.value.trim()) {
                    // Add typing indicator
                    const typingDiv = document.createElement('div');
                    typingDiv.className = 'flex justify-start mb-4';
                    typingDiv.innerHTML = `
                        <div class="flex items-start space-x-4 max-w-md">
                            <div class="w-10 h-10 bg-gradient-to-r from-violet-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                <span class="text-white font-bold">G</span>
                            </div>
                            <div class="glass-card text-gray-800 px-6 py-4 rounded-2xl shadow-md">
                                <div class="message-typing flex space-x-1">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                </div>
                            </div>
                        </div>
                    `;
                    if (chatContainer) {
                        chatContainer.appendChild(typingDiv);
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    }
                }
            });
        }

        // Enhanced mood selection with haptic feedback (if supported)
        const moodButtons = document.querySelectorAll('.mood-button');
        moodButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Haptic feedback for mobile devices
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }

                // Visual feedback
                const card = this.querySelector('div');
                card.classList.add('animate-bounce-gentle');
                setTimeout(() => {
                    card.classList.remove('animate-bounce-gentle');
                }, 600);
            });
        });

        // Activity chip animations
        const activityChips = document.querySelectorAll('.activity-chip');
        activityChips.forEach(chip => {
            chip.addEventListener('click', function() {
                if (navigator.vibrate) {
                    navigator.vibrate(30);
                }
            });
        });

        // Auto-expand textarea
        const textarea = document.querySelector('textarea[name="journal"]');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        }

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        }, observerOptions);

        // Observe elements for scroll animations
        document.querySelectorAll('.glass-morphism, .glass-card').forEach(el => {
            observer.observe(el);
        });
    });
</script>
</body>
</html>
