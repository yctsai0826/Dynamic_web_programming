// 遊戲狀態與變量
let playerScore = 0;
let dealerScore = 0;
let dealerScoreshow = 0;
let playerCards = [];
let dealerCards = [];
let chips = parseInt(localStorage.getItem('chips')) || 100; // 從 localStorage 取得籌碼
let betAmount = 0;
let raiseAmount = 0;
let gamestarted = false;
const cardDeck = generateCardDeck(); // 生成一副牌

// DOM 元素選擇
const playerCardsContainer = document.getElementById('player-cards');
const dealerCardsContainer = document.getElementById('dealer-cards');
const playerScoreText = document.getElementById('player-score');
const RoundChips = document.getElementById('round-chips');
const dealerScoreText = document.getElementById('dealer-score');
const chipsText = document.getElementById('chips-amount');
const hitButton = document.getElementById('hit-button');
const standButton = document.getElementById('stand-button');
const newGameButton = document.getElementById('new-game-button');
const betButton = document.getElementById('bet-button');
const raiseButton = document.getElementById('raise-button');
// document.getElementById('game-container').appendChild(raiseButton);
const viewHistoryButton = document.getElementById('view-history-button');
// document.getElementById('game-container').appendChild(viewHistoryButton);

function generateCardDeck() {
    const suits = ['h', 'd', 'c', 's'];
    const values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    const deck = [];
    for (const suit of suits) {
        for (const value of values) {
            deck.push({ value, suit });
        }
    }
    return deck;
}

function shuffleDeck(deck) {
    for (let i = deck.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [deck[i], deck[j]] = [deck[j], deck[i]];
    }
}

function drawCard() {
    return cardDeck.pop();
}

function displayCard(card, container, isFaceDown = false, animation = true) {
    const img = document.createElement('img');
    img.src = isFaceDown ? './poker/back.jpg' : `./poker/${card.suit}_${card.value}.jpg`;

    if (animation) {
        img.className = 'card-slide-up'; // 添加动画类
        setTimeout(() => {
            img.classList.add('show');
        }, 100);
    }
    container.appendChild(img);
}


function calculateScore(cards) {
    let score = 0;
    let hasAce = false;
    for (const card of cards) {
        if (['J', 'Q', 'K'].includes(card.value)) {
            score += 10;
        } else if (card.value === 'A') {
            hasAce = true;
            score += 11;
        } else {
            score += parseInt(card.value);
        }
    }
    if (hasAce && score > 21) {
        score -= 10;
    }
    return score;
}

function updateScores() {
    playerScore = calculateScore(playerCards);
    dealerScore = calculateScore(dealerCards);
    playerScoreText.textContent = `Score: ${playerScore}`;
    dealerScoreText.textContent = `Score: ${dealerScoreshow}`;
    RoundChips.textContent = `Current round - chips placed: ${betAmount + raiseAmount}`;
}

function startNewGame() {
    chipsText.textContent = chips;
    newGameButton.style.display = 'block';
    hitButton.disabled = true;
    standButton.disabled = true;
    raiseButton.disabled = true;
    betButton.disabled = false;
    if (betAmount === 0) {
        alert('請先下注(Assign)才能開始新遊戲(New round)');
        return;
    }
    // start a new game
    newGameButton.style.display = 'none';
    gamestarted = true;
    playerCards = [drawCard(), drawCard()];
    dealerCards = [drawCard(), drawCard()];
    playerCardsContainer.innerHTML = '';
    dealerCardsContainer.innerHTML = '';

    playerCards.forEach(card => displayCard(card, playerCardsContainer));
    displayCard(dealerCards[0], dealerCardsContainer);
    displayCard(dealerCards[1], dealerCardsContainer, true);

    updateScores();
    // newGameButton.style.display = 'none';
    hitButton.disabled = false;
    standButton.disabled = false;
    raiseButton.disabled = false;
    betButton.disabled = true;
    // saveGameData(); 
}

function hit() {
    if (betAmount === 0) {
        alert('請先下注(Assign)才能開抽牌(Hit)');
        return;
    }
    else if (!gamestarted) {
        alert('請先開始新遊戲(New round)！');
        return;
    }
    raiseButton.disabled = false;
    playerCards.push(drawCard());
    displayCard(playerCards[playerCards.length - 1], playerCardsContainer);
    updateScores();
    if (playerScore > 21) {
        endGame('你輸了！');
    }
}

function stand() {
    if (gamestarted === false) {
        alert('請先下注(Assign)，再開始新遊戲(New round)！');
        return;
    }
    hitButton.disabled = true;
    standButton.disabled = true;
    raiseButton.disabled = true;
    while (dealerScore < 17) {
        dealerCards.push(drawCard());
        displayCard(dealerCards[dealerCards.length - 1], dealerCardsContainer);
        updateScores();
    }
    if (dealerScore > 21 || playerScore > dealerScore) {
        endGame('你贏了！');
    } else if (playerScore === dealerScore) {
        endGame('平手');
    }
    else {
        endGame('你輸了！');
    }
}

function revealDealerCards() {
    dealerCardsContainer.innerHTML = '';
    // dealerCards.forEach(card => displayCard(card, dealerCardsContainer));
    dealerCards.forEach((card, index) => {
        if (index > 0) {
            displayCard(card, dealerCardsContainer);
        } else {
            displayCard(card, dealerCardsContainer, false, false);
        }
    });
}

function endGame(message) {
    gamestarted = false;
    alert(message);
    revealDealerCards();
    dealerScoreshow = dealerScore;
    updateScores();
    let changeAmount = betAmount + raiseAmount;
    if (message === '你贏了！') {
        updateChips(changeAmount * 2);
        const winGifContainer = document.getElementById('win-gif-container');
        winGifContainer.style.display = 'flex';
        const overlay = document.getElementById('overlay');
        overlay.style.display = 'block';

        setTimeout(() => {
            winGifContainer.style.display = 'none';
            overlay.style.display = 'none';
        }, 3000);
    }
    else if (message === '你輸了！') {
        updateChips(0);
        const winGifContainer = document.getElementById('lose-gif-container');
        winGifContainer.style.display = 'flex';
        const overlay = document.getElementById('overlay');
        overlay.style.display = 'block';

        // 設置定時器在幾秒後隱藏 GIF
        setTimeout(() => {
            winGifContainer.style.display = 'none';
            overlay.style.display = 'none';
        }, 3000); // 3秒後隱藏 GIF，您可以根據需要調整時間
    }
    else if (message === '平手') {
        updateChips(changeAmount);
    }
    saveRoundData(message, changeAmount);
    resetGame();
}

function resetGame() {
    betAmount = 0;
    raiseAmount = 0;
    dealerScoreshow = 0;
    RoundChips.textContent = `Current round - chips placed: ${betAmount + raiseAmount}`;
    chipsText.textContent = chips;
    newGameButton.style.display = 'block';
    hitButton.disabled = true;
    standButton.disabled = true;
    betButton.disabled = false;
    raiseButton.disabled = true;
}

function updateChips(amount) {
    chips += amount;
    chipsText.textContent = chips;
    localStorage.setItem('chips', chips);
}

function saveRoundData(message, changeAmount) {
    if (message === '平手') {
        changeAmount = 0;
    }
    else if (message === '你輸了！') {
        changeAmount *= -1;
    }
    const roundData = {
        playerCards,
        dealerCards,
        result: message,
        changeAmount
    };
    let history = JSON.parse(sessionStorage.getItem('gameHistory')) || [];
    history.push(roundData);
    sessionStorage.setItem('gameHistory', JSON.stringify(history));
}

function showHistory() {
    const history = JSON.parse(sessionStorage.getItem('gameHistory')) || [];
    const historyWindow = window.open('', 'Game history', 'width=800,height=600');
    historyWindow.document.write(`
        <html>
        <head>
            <style>
                @font-face {
                    font-family: 'enfont';
                    src: url('./font/enfont.ttf') format('truetype');
                }
                body {
                    font-family: 'enfont', sans-serif; /* 替換為您想使用的字體 */
                    margin: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    border: 1px solid #ccc;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
                img {
                    margin: 2px;
                }
            </style>
        </head>
        <body>
            <h2>Game history</h2>
            <table>
                <thead>
                    <tr>
                        <th>Round</th>
                        <th>Result</th>
                        <th>Chips' Change</th>
                        <th>Your Cards</th>
                        <th>Your Total</th>
                        <th>Dealer Cards</th>
                        <th>Dealer Total</th>
                    </tr>
                </thead>
                <tbody>
    `);

    history.forEach((round, index) => {
        const playerCardsImages = round.playerCards.map(card => 
            `<img src="poker/${card.suit}_${card.value}.jpg" alt="${card.value}${card.suit}" width="60">`
        ).join('');
        const playerTotal = calculateScore(round.playerCards);

        const dealerCardsImages = round.dealerCards.map(card => 
            `<img src="poker/${card.suit}_${card.value}.jpg" alt="${card.value}${card.suit}" width="60">`
        ).join('');
        const dealerTotal = calculateScore(round.dealerCards);

        historyWindow.document.write(`
            <tr>
                <td>${index + 1}</td>
                <td>${round.result}</td>
                <td>${round.changeAmount}</td>
                <td>${playerCardsImages}</td>
                <td>${playerTotal}</td>
                <td>${dealerCardsImages}</td>
                <td>${dealerTotal}</td>
            </tr>
        `);
    });

    historyWindow.document.write(`
                </tbody>
            </table>
        </body>
        </html>
    `);
}


betButton.addEventListener('click', () => {
    if (chips >= 10) {
        if (betAmount > 0) {
            alert('不能下注(Assign)了，請  先開始新回合再加注(Signal)！');
            return;
        }
        betAmount += 10;
        RoundChips.textContent = `Current round - chips placed: ${betAmount}`;
        updateChips(-10);
    } else {
        alert('你已經沒籌碼了！');
    }
});

raiseButton.addEventListener('click', () => {
    if (playerCards.length >= 3) {
        alert('你無法加注，因為你已經抽牌了！');
        return;
    }
    if (!gamestarted) {
        alert('請先開始新遊戲(New round)才能進行加注！');
        return;
    }
    if (chips >= 10 && betAmount > 0) {
        raiseAmount += 10;
        updateChips(-10);
        RoundChips.textContent = `Current round - chips placed: ${betAmount + raiseAmount}`;
    } else {
        alert('你無法加注！');
    }
});

viewHistoryButton.addEventListener('click', showHistory);
hitButton.addEventListener('click', hit);
standButton.addEventListener('click', stand);
newGameButton.addEventListener('click', startNewGame);

alert('歡迎來到 21 點遊戲！\n請先下注(Assign)，再開始新遊戲(New round)！\nNotice: 加注(Signal)只能在開始新遊戲(New round)後，且在抽牌(Hit)前使用！');
shuffleDeck(cardDeck);
startNewGame();
