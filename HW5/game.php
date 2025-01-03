<?php

// 111550035 蔡昀錚第5次作業12/6
// 111550035 Yun-Cheng Tsai The Fifth Homework 12/6

session_start();

if (!isset($_SESSION['deck'])) {
    alert('歡迎來到 21 點遊戲！\n請先下注(Assign)，再開始新遊戲(New round)！\nNotice: 加注(Signal)只能在開始新遊戲(New round)後，且在抽牌(Hit)前使用！');
    initializeGame();
    $init = true;
}

// 處理請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'start_new_game':
                startNewGame();
                break;
            case 'hit':
                hit();
                break;
            case 'stand':
                stand();
                break;
            case 'bet':
                bet();
                break;
            case 'raise':
                raiseAmount();
                break;
            case 'view_history':
                viewHistory();
                exit;
            case 'cheat':
                cheat();
                break;
            default:
                break;
        }
    }
}

function alert($msg) {
    echo "<script type='text/javascript'>alert('$msg');</script>";
}

function loadGameHistory() {
    // 連接資料庫
    $conn = new mysqli('localhost', 'root', '', 'GameDB');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
        return;
    }

    // 準備查詢
    $sql = "SELECT all_game_data, chips FROM game_history WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        return;
    }

    // 綁定參數並執行
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    // 如果未找到記錄，直接返回
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return;
    }

    // 如果找到記錄，處理數據
    $row = $result->fetch_assoc();
    $gameHistory = json_decode($row['all_game_data'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $stmt->close();
        $conn->close();
        return;
    }

    // 更新會話資料
    $_SESSION['chips'] = $row['chips'];
    $_SESSION['gameHistory'] = $gameHistory;
    $stmt->close();
    $conn->close();

}


function saveGameToCookie() {
    $dataToSave = [
        'gameHistory' => $_SESSION['gameHistory']
    ];
    $jsonEncodedData = json_encode($dataToSave);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Failed to encode game data.']);
        return;
    }

    setcookie('gameInfo', $jsonEncodedData, time() + 3600, '/');
}

function saveGameToDatabase() {
    $conn = new mysqli('localhost', 'root', '', 'GameDB');
    if ($conn->connect_error) {
        json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
        return;
    }

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['gameHistory']) || !isset($_SESSION['chips'])) {
        json_encode(['success' => false, 'error' => 'Missing session data.']);
        $conn->close();
        return;
    }

    $user_id = $_SESSION['user_id'];
    $gameHistory = $_SESSION['gameHistory'];
    $chips = $_SESSION['chips'];

    $allGameData = json_encode($gameHistory);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_encode(['success' => false, 'error' => 'JSON encoding failed: ' . json_last_error_msg()]);
        $conn->close();
        return;
    }

    $stmt = $conn->prepare("INSERT INTO game_history (user_id, all_game_data, chips) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        all_game_data = VALUES(all_game_data), chips = VALUES(chips)");

    if (!$stmt) {
        json_encode(['success' => false, 'error' => 'Statement preparation failed: ' . $conn->error]);
        $conn->close();
        return;
    }

    $stmt->bind_param("isi", $user_id, $allGameData, $chips);

    if (!$stmt->execute()) {
        json_encode(['success' => false, 'error' => 'Execution failed: ' . $stmt->error]);
    } else {
        json_encode(['success' => true, 'message' => 'Game data saved successfully.']);
    }

    $stmt->close();
    $conn->close();
}


function loadGameFromCookie() {
    if (!isset($_COOKIE['gameInfo'])) {
        return;
    }

    $cookieData = $_COOKIE['gameInfo'];
    $decodedData = json_decode(urldecode($cookieData), true);

    if (isset($decodedData['gameHistory']) && isset($decodedData['chips'])) {
        $_SESSION['gameHistory'] = $decodedData['gameHistory']; // 遊戲歷史記錄
    }
}




// 遊戲初始化函數
function initializeGame() {
    $_SESSION['deck'] = generateCardDeck();
    shuffle($_SESSION['deck']);
    $_SESSION['chips'] = 1000;
    $_SESSION['gameHistory'] = [];
    $_SESSION['playerCards'] = [];
    $_SESSION['dealerCards'] = [];
    $_SESSION['betAmount'] = 0;
    $_SESSION['gamestarted'] = false;
    loadGameHistory();
    saveGameToCookie();
}

// 生成一副牌
function generateCardDeck() {
    $suits = ['h', 'd', 'c', 's'];
    $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    $deck = [];
    foreach ($suits as $suit) {
        foreach ($values as $value) {
            $deck[] = ['value' => $value, 'suit' => $suit];
        }
    }
    return $deck;
}

// 抽牌
function drawCard() {
    if (empty($_SESSION['deck'])) {
        $_SESSION['deck'] = generateCardDeck();
        shuffle($_SESSION['deck']);
    }
    return array_pop($_SESSION['deck']);
}

// 計算分數
function calculateScore($cards) {
    $score = 0;
    $hasAce = false;
    foreach ($cards as $card) {
        if (in_array($card['value'], ['J', 'Q', 'K'])) {
            $score += 10;
        } elseif ($card['value'] === 'A') {
            $score += 11;
            $hasAce = true;
        } else {
            $score += intval($card['value']);
        }
    }
    if ($hasAce && $score > 21) {
        $score -= 10;
    }
    return $score;
}

// 開始新遊戲
function startNewGame() {
    loadGameFromCookie();
    if ($_SESSION['betAmount'] === 0) {
        alert('請先下注！');
        return;
    }
    $_SESSION['gamestarted'] = true;
    $_SESSION['playerCards'] = [drawCard(), drawCard()];
    $_SESSION['dealerCards'] = [drawCard(), drawCard()];
    return;
}

// 玩家抽牌
function hit() {
    if (!$_SESSION['gamestarted']) {
        alert('請先開始新遊戲！');
        return;
    }
    $_SESSION['playerCards'][] = drawCard();
    $playerScore = calculateScore($_SESSION['playerCards']);
    if ($playerScore > 21) {
        endGame("你輸了！");
        return;
    }
    return;
}

// 玩家停止抽牌
function stand() {
    if (!$_SESSION['gamestarted']) {
        alert('請先開始新遊戲！');
        return;
    }
    while (calculateScore($_SESSION['dealerCards']) < 17) {
        $_SESSION['dealerCards'][] = drawCard();
    }
    $playerScore = calculateScore($_SESSION['playerCards']);
    $dealerScore = calculateScore($_SESSION['dealerCards']);
    if ($dealerScore > 21 || $playerScore > $dealerScore) {
        endGame("你贏了！");
        return;
    } elseif ($playerScore === $dealerScore) {
        endGame("平手");
        return;
    }
    endGame("你輸了！");
    return;
}

// 玩家下注
function bet() {
        if ($_SESSION['chips'] >= 10 && $_SESSION['betAmount'] === 0) {
        $_SESSION['betAmount'] += 10;
        $_SESSION['chips'] -= 10;
        return ['betAmount' => $_SESSION['betAmount'], 'chips' => $_SESSION['chips']];
    }
}

// 玩家加注
function raiseAmount() {
    if ($_SESSION['chips'] >= 10 && $_SESSION['betAmount'] > 0) {
        $_SESSION['betAmount'] += 10;
        $_SESSION['chips'] -= 10;
    }
    else{
        alert('當前無法加注！');
    }
}

// 查看歷史記錄
function viewHistory() {
    loadGameHistory();
    echo '<h2>Game History</h2><table border="1">';
    echo '<tr><th>Round</th><th>Result</th><th>Chips Change</th><th>Your Cards</th><th>Your Score</th><th>Dealer Cards</th><th>Dealer Score</th></tr>';
    foreach ($_SESSION['gameHistory'] as $index => $round) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . $round['result'] . '</td>';
        echo '<td>' . ($round['changeAmount'] ?? 0) . '</td>';
        echo '<td>' . formatCards($round['playerCards']) . '</td>';
        echo '<td>' . ($round['playerScore'] ?? 0) . '</td>';
        echo '<td>' . formatCards($round['dealerCards']) . '</td>';
        echo '<td>' . ($round['dealerScore'] ?? 0) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

function cheat() {
    endGame("你贏了！");
    return;
}

// 格式化卡片顯示
function formatCards($cards, $isDealer = false) {
    $html = '';
    foreach ($cards as $index => $card) {
        if ($isDealer && $index === 0) {
            // 莊家第一張牌顯示為背面
            $html .= '<img src="poker/back.jpg" alt="back" width="100">';
        } else {
            // 顯示普通卡片，圖片大小調整為更大
            $html .= '<img src="poker/' . $card['suit'] . '_' . $card['value'] . '.jpg" alt="' . $card['value'] . '" width="100">';
        }
    }
    return $html;
}


// 結束遊戲
function endGame($result) {
    alert($result);
    $_SESSION['gamestarted'] = false;
    $changeAmount = $_SESSION['betAmount'];
    if ($result === "你贏了！") {
        $_SESSION['chips'] += $changeAmount * 2;
    } elseif ($result === "平手") {
        $_SESSION['chips'] += $changeAmount;
    }

    $roundData = [
        'result' => $result,
        'playerCards' => $_SESSION['playerCards'],
        'dealerCards' => $_SESSION['dealerCards'],
        'changeAmount' => $changeAmount,
        'playerScore' => calculateScore($_SESSION['playerCards']),
        'dealerScore' => calculateScore($_SESSION['dealerCards']),
    ];

    $_SESSION['gameHistory'][] = $roundData;

    saveGameToCookie();
    saveGameToDatabase();
    resetGame();
    return;
}

// 重置遊戲
function resetGame() {
    $_SESSION['betAmount'] = 0;
    $_SESSION['playerCards'] = [];
    $_SESSION['dealerCards'] = [];
}

echo '<div class="logout-button">
    <form action="logout.php" method="POST">
        <input type="image" src="img/logout.png" alt="Log out" class="transparent-button" />
    </form>
</div>';

?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>21點遊戲</title>
    <link rel="stylesheet" href="game_style.css">
</head>
<body>
<h1>歡迎來到 21 點遊戲</h1>
<div>
    <h2>莊家的牌：</h2>
    <div>
        <?= isset($_SESSION['dealerCards']) ? formatCards($_SESSION['dealerCards'], true) : '' ?>
    </div>
</div>
<div>
    <h2>玩家的牌：</h2>
    <div>
        <?= isset($_SESSION['playerCards']) ? formatCards($_SESSION['playerCards'], false) : '' ?>
    </div>
    <h2>玩家分數：<?= calculateScore($_SESSION['playerCards']) ?? 0 ?></h2>
</div>
<div>
    <form method="POST">
        <button name="action" value="start_new_game">新遊戲</button>
        <button name="action" value="bet">下注</button>
        <button name="action" value="raise">加注</button>
        <button name="action" value="hit">抽牌</button>
        <button name="action" value="stand">停止</button>
        <button name="action" value="cheat">作弊</button>
        <button name="action" value="view_history">遊戲歷史</button>
    </form>
</div>
<div>
    <h2>已下注籌碼：<?= $_SESSION['betAmount'] ?? 0 ?></h2>
    <h2>玩家籌碼：<?= $_SESSION['chips'] ?? 0 ?></h2>
</div>
</body>
</html>

