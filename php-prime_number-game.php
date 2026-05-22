<?php
// --- PHPのロジック部分（リクエストがあった時だけ判定を返す） ---
if (isset($_GET['action']) && $_GET['action'] === 'check') {
    header('Content-Type: application/json');
    $num = isset($_GET['num']) ? (int)$_GET['num'] : 0;
    
    // 素数判定
    $isPrime = true;
    $factor = 1;
    if ($num < 2) {
        $isPrime = false;
    } else {
        for ($i = 2; $i * $i <= $num; $i++) {
            if ($num % $i === 0) {
                $isPrime = false;
                $factor = $i; // 割り切れた数を記録
                break;
            }
        }
    }
    
    echo json_encode([
        'isPrime' => $isPrime,
        'factor' => $factor
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✨ PRIME RUSH - 素数判定ゲーム -</title>
    <style>
        /* --- オシャレなダークモード風の近未来デザイン --- */
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --accent-prime: #10b981; /* 緑 */
            --accent-not-prime: #f43f5e; /* 赤 */
            --text-color: #f8fafc;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .game-container {
            background-color: var(--card-bg);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 400px;
            max-width: 90%;
            position: relative;
        }

        h1 {
            font-size: 1.8rem;
            margin-top: 0;
            letter-spacing: 2px;
            background: linear-gradient(to right, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .status-bar {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #94a3b8;
            margin-bottom: 2rem;
        }

        /* 出題される数字のスタイル */
        .number-display {
            font-size: 5rem;
            font-weight: 800;
            margin: 2rem 0;
            font-variant-numeric: tabular-nums;
            transition: transform 0.3s ease;
        }

        /* ボタンのレイアウト */
        .button-group {
            display: flex;
            gap: 1rem;
        }

        .btn {
            flex: 1;
            padding: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
        }

        .btn-prime {
            background-color: var(--accent-prime);
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }
        .btn-prime:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); }

        .btn-not-prime {
            background-color: var(--accent-not-prime);
            box-shadow: 0 4px 14px rgba(244, 63, 94, 0.3);
        }
        .btn-not-prime:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(244, 63, 94, 0.4); }

        /* 結果アニメーション用のオーバーレイ */
        .feedback {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 3rem;
            font-weight: bold;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 10;
        }
        .feedback.correct { background: rgba(16, 185, 129, 0.9); opacity: 1; }
        .feedback.wrong { background: rgba(244, 63, 94, 0.9); opacity: 1; }
        .feedback-reason { font-size: 1.2rem; margin-top: 1rem; font-weight: normal; }

        /* リザルト画面 */
        .result-screen {
            display: none;
        }
        .btn-restart {
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            margin-top: 1.5rem;
            width: 100%;
        }
    </style>
</head>
<body>

<div class="game-container">
    <!-- 結果表示用のエフェクト画面 -->
    <div id="feedbackOverlay" class="feedback">
        <span id="feedbackIcon">⭕</span>
        <div id="feedbackReason" class="feedback-reason"></div>
    </div>

    <!-- プレイ画面 -->
    <div id="playScreen">
        <h1>PRIME RUSH</h1>
        <div class="status-bar">
            <div>問題: <span id="currentQuestion">1</span> / 5</div>
            <div>スコア: <span id="currentScore">0</span></div>
        </div>

        <div id="numberDisplay" class="number-display">--</div>

        <div class="button-group">
            <button class="btn btn-prime" onclick="answer(true)">素数 (Prime)</button>
            <button class="btn btn-not-prime" onclick="answer(false)">合成数 (Not)</button>
        </div>
    </div>

    <!-- リザルト画面 -->
    <div id="resultScreen" class="result-screen">
        <h1>GAME OVER</h1>
        <p style="font-size: 1.2rem; margin: 2rem 0;">
            あなたのスコア: <span id="finalScore" style="font-size: 2rem; font-weight: bold; color: #60a5fa;">0</span> / 5
        </p>
        <p id="rankMessage" style="color: #94a3b8;"></p>
        <button class="btn btn-restart" onclick="resetGame()">もう一度あそぶ</button>
    </div>
</div>

<script>
// --- JavaScriptによるゲーム進行と非同期通信(Fetch API) ---
let score = 0;
let questionCount = 1;
const maxQuestions = 5;
let currentNumber = 0;

function generateNumber() {
    // 2〜100の間でランダムな数を作る
    return Math.floor(Math.random() * 99) + 2;
}

function nextQuestion() {
    if (questionCount > maxQuestions) {
        showResult();
        return;
    }
    
    document.getElementById('currentQuestion').textContent = questionCount;
    currentNumber = generateNumber();
    
    const numEl = document.getElementById('numberDisplay');
    numEl.textContent = currentNumber;
    
    // ふわっと数字を出すアニメーション
    numEl.style.transform = 'scale(0.8)';
    setTimeout(() => numEl.style.transform = 'scale(1)', 50);
}

function answer(userChosePrime) {
    // PHPに答えを問い合わせる (非同期通信)
    fetch(`?action=check&num=${currentNumber}`)
        .then(res => res.json())
        .then(data => {
            const isCorrect = (userChosePrime === data.isPrime);
            if (isCorrect) score++;
            
            // 画面更新
            document.getElementById('currentScore').textContent = score;
            
            // ⭕❌の演出を表示
            const overlay = document.getElementById('feedbackOverlay');
            const icon = document.getElementById('feedbackIcon');
            const reason = document.getElementById('feedbackReason');
            
            if (isCorrect) {
                overlay.className = "feedback correct";
                icon.textContent = "⭕ 正解！";
                reason.textContent = "";
            } else {
                overlay.className = "feedback wrong";
                icon.textContent = "❌ 残念！";
                reason.textContent = data.isPrime 
                    ? `${currentNumber} は素数です` 
                    : `${currentNumber} は合成数 (${data.factor}で割れます)`;
            }
            
            // 1.2秒後に次の問題へ
            setTimeout(() => {
                overlay.className = "feedback"; // 非表示に戻す
                questionCount++;
                nextQuestion();
            }, 1200);
        });
}

function showResult() {
    document.getElementById('playScreen').style.display = 'none';
    document.getElementById('resultScreen').style.display = 'block';
    document.getElementById('finalScore').textContent = score;
    
    const msgEl = document.getElementById('rankMessage');
    if (score === 5) msgEl.textContent = "👑 神レベル！完璧な素数マスターです！";
    else if (score >= 3) msgEl.textContent = "👍 素晴らしい！数字のセンスがありますね！";
    else msgEl.textContent = "🧐 どんまい！繰り返し遊んで感覚を掴もう！";
}

function resetGame() {
    score = 0;
    questionCount = 1;
    document.getElementById('currentScore').textContent = score;
    document.getElementById('playScreen').style.display = 'block';
    document.getElementById('resultScreen').style.display = 'none';
    nextQuestion();
}

// ゲーム開始
nextQuestion();
</script>
</body>
</html>
