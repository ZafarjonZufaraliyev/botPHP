<?php
echo "✅ Bot ishga tushdi...\n";

// === Telegram sozlamalari ===
$token = "7989771120:AAEDQTJjmawBswoVrCqPa4jvnB4Di5QaONM"; // 🔁 <-- bu yerga o'z tokeningizni yozing
$apiURL = "https://api.telegram.org/bot$token/";

// === Kanal majburiy obuna ro'yxati ===
$REQUIRED_CHANNELS = [
    [ "username" => "@stathim_jason", "chatId" => null, "displayName" => "1-chi kanal", "url" => "https://t.me/stathim_jason" ],
    [ "username" => null, "chatId" => -1002894702391, "displayName" => "2-chi kanal", "url" => "https://t.me/+Rhj3QGVMOG45MzQy" ]
];

// === Foydalanuvchi holatini saqlash uchun fayl ===
$stateFile = __DIR__ . "/states.json";
if (!file_exists($stateFile)) file_put_contents($stateFile, "{}");
$userStates = json_decode(file_get_contents($stateFile), true);

// === Foydali funksiya: so‘rov yuborish ===
function tgRequest($method, $params = []) {
    global $apiURL;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiURL . $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// === Kanalga a'zolikni tekshirish ===
function checkSubscription($userId) {
    global $REQUIRED_CHANNELS, $token;
    foreach ($REQUIRED_CHANNELS as $chan) {
        $chatIdParam = $chan["chatId"] ?? $chan["username"];
        if (!$chatIdParam) continue;
        $url = "https://api.telegram.org/bot$token/getChatMember?chat_id=" . urlencode($chatIdParam) . "&user_id=" . $userId;
        $res = @json_decode(file_get_contents($url), true);
        if (!isset($res["ok"]) || !$res["ok"]) return false;
        $status = $res["result"]["status"] ?? "";
        if ($status === "left" || $status === "kicked") return false;
    }
    return true;
}

// === Boshlang‘ich xabar ===
function sendWelcomeMessage($chatId) {
    $msg = "🎉 <b>Xush kelibsiz!</b>\n\n".
           "🆘 <b>Eslatma:</b>\n".
           "1️⃣ 1xBET yoki Linebet ilovasidan ro‘yxatdan o‘ting.\n".
           "2️⃣ Promokod joyiga <b>BEKA04</b> yozing.\n".
           "3️⃣ Hisobni to‘ldiring.\n\n".
           "<i>Davom etish uchun quyidagi tugmani bosing 👇</i>";

    $keyboard = [
        "inline_keyboard" => [
            [ ["text" => "📹 Qo'llanma", "callback_data" => "guide_video"] ],
            [ ["text" => "▶️ Davom ettirish", "callback_data" => "continue_app"] ]
        ]
    ];
    tgRequest("sendMessage", [
        "chat_id"=>$chatId,
        "text"=>$msg,
        "parse_mode"=>"HTML",
        "reply_markup"=>json_encode($keyboard)
    ]);
}

// === Kanalga obuna bo‘lishni so‘rash ===
function sendChannelRequest($chatId) {
    global $REQUIRED_CHANNELS;
    $keyboard = [];
    foreach ($REQUIRED_CHANNELS as $chan) {
        if (!empty($chan["url"])) $keyboard[] = [ ["text"=>$chan["displayName"], "url"=>$chan["url"]] ];
    }
    $keyboard[] = [ ["text"=>"✅ A'zo bo'ldim", "callback_data"=>"azo_boldim"] ];
    tgRequest("sendMessage", [
        "chat_id"=>$chatId,
        "text"=>"❗ Iltimos, quyidagi kanallarga obuna bo‘ling:",
        "reply_markup"=>json_encode(["inline_keyboard"=>$keyboard])
    ]);
}

// === Ilova tanlash (1xBet / Linebet) ===
function sendAppSelection($chatId) {
    $keyboard = [
        "inline_keyboard" => [
            [ ["text" => "📱 1xBET", "callback_data" => "app_a"], ["text" => "📱 Linebet", "callback_data" => "app_b"] ]
        ]
    ];
    tgRequest("sendMessage", [
        "chat_id"=>$chatId,
        "text"=>"Qaysi ilovadan ro‘yxatdan o‘tgansiz?",
        "parse_mode"=>"HTML",
        "reply_markup"=>json_encode($keyboard)
    ]);
}

// === Qo‘llanma video ===
function sendGuideVideo($chatId) {
    $fileName = "apple.mp4";
    $filePath = __DIR__ . "/" . $fileName;
    if (!file_exists($filePath)) {
        tgRequest("sendMessage", ["chat_id"=>$chatId, "text"=>"❗ Video topilmadi: $fileName"]);
        return;
    }
    $cfile = new CURLFile($filePath);
    tgRequest("sendVideo", [
        "chat_id" => $chatId,
        "video"   => $cfile,
        "caption" => "📹 Qo‘llanma video"
    ]);
}

// === Signal chiqarish ===
function sendSignalMessage($chatId) {
    $random = rand(1, 5);
    $text = "✅ <b>Signal:</b> $random";

    $keyboard = [
        "inline_keyboard" => [
            [ ["text" => "🔥 Yana signal olish", "callback_data" => "get_signal"] ]
        ]
    ];

    tgRequest("sendMessage", [
        "chat_id"=>$chatId,
        "text"=>$text,
        "parse_mode"=>"HTML",
        "reply_markup"=>json_encode($keyboard)
    ]);
}

// === Long polling orqali xabarlarni qabul qilish ===
$offset = 0;
while (true) {
    $updates = tgRequest("getUpdates", ["offset" => $offset + 1, "timeout" => 10]);
    if (!empty($updates["result"])) {
        foreach ($updates["result"] as $update) {
            $offset = $update["update_id"];
            global $userStates;

            // === Oddiy xabar ===
            if (isset($update["message"])) {
                $chatId = $update["message"]["chat"]["id"];
                $userId = $update["message"]["from"]["id"];
                $text   = $update["message"]["text"] ?? '';
                $state  = $userStates[$userId]["step"] ?? "";
                $app    = $userStates[$userId]["app"] ?? "";

                if ($text === "/start") {
                    if (!checkSubscription($userId)) {
                        sendChannelRequest($chatId);
                        $userStates[$userId]["step"] = "waiting_subscription";
                    } else {
                        sendWelcomeMessage($chatId);
                        $userStates[$userId]["step"] = "start";
                    }
                } 
                elseif ($state === "waiting_beka04_id") {
                    if (preg_match('/^[0-9]{10}$/', trim($text))) {
                        $appName = $app ?: "1xBET";
                        $msg = "📲 {$appName} ID qabul qilindi!\n\n".
                               "⚠️ Agar <b>BEKA04</b> promokoddan o‘tmagan bo‘lsangiz, bot xato signal beradi!\n\n".
                               "<i>Endi pastdagi tugma orqali signal oling:</i>";

                        $keyboard = [
                            "inline_keyboard" => [
                                [ ["text" => "🔥 Signal olish", "callback_data" => "get_signal"] ]
                            ]
                        ];

                        tgRequest("sendMessage", [
                            "chat_id" => $chatId,
                            "text" => $msg,
                            "parse_mode" => "HTML",
                            "reply_markup" => json_encode($keyboard)
                        ]);

                        $userStates[$userId]["step"] = "ready_signal";
                    } else {
                        tgRequest("sendMessage", ["chat_id"=>$chatId, "text"=>"❗ Faqat raqamli ID yuboring!"]);
                    }
                }
            }

            // === Callback tugmalarni boshqarish ===
            if (isset($update["callback_query"])) {
                $data   = $update["callback_query"]["data"];
                $chatId = $update["callback_query"]["message"]["chat"]["id"];
                $userId = $update["callback_query"]["from"]["id"];

                tgRequest("answerCallbackQuery", ["callback_query_id" => $update["callback_query"]["id"]]);

                if ($data === "guide_video") sendGuideVideo($chatId);
                elseif ($data === "azo_boldim") {
                    if (!checkSubscription($userId)) {
                        tgRequest("sendMessage", ["chat_id"=>$chatId, "text"=>"❗ Siz hali kanallarga obuna bo‘lmadingiz."]);
                    } else {
                        sendWelcomeMessage($chatId);
                        $userStates[$userId]["step"] = "waiting_continue";
                    }
                } elseif ($data === "continue_app") {
                    $userStates[$userId]["step"] = "start";
                    sendAppSelection($chatId);
                } elseif ($data === "app_a") {
                    $userStates[$userId]["step"] = "waiting_beka04_id";
                    $userStates[$userId]["app"]  = "1xBET";
                    tgRequest("sendMessage", ["chat_id"=>$chatId, "text"=>"✅ BEKA04 promokoddan o‘tgan ID yuboring:", "parse_mode"=>"HTML"]);
                } elseif ($data === "app_b") {
                    $userStates[$userId]["step"] = "waiting_beka04_id";
                    $userStates[$userId]["app"]  = "Linebet";
                    tgRequest("sendMessage", ["chat_id"=>$chatId, "text"=>"✅ BEKA04 promokoddan o‘tgan ID yuboring:", "parse_mode"=>"HTML"]);
                } elseif ($data === "get_signal") {
                    sendSignalMessage($chatId);
                }
            }

            // === Foydalanuvchi holatini saqlash ===
            file_put_contents($GLOBALS['stateFile'], json_encode($GLOBALS['userStates'], JSON_PRETTY_PRINT));
        }
    }
    sleep(1);
}
