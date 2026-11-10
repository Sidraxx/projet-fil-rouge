const teamBuilder = document.querySelector('#team-builder');
const combatBoard = document.querySelector('#combat-board');
const availableCards = [...document.querySelectorAll('.combat-card')];
const teamCounter = document.querySelector('#team-counter');
const startCombatButton = document.querySelector('#start-combat');
const teamMessage = document.querySelector('#team-message');
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

let selectedMovieIds = [];
let combatState = null;
let selectedEnemyIndex = 0;
let selectedPlayerIndex = 0;

function escapeHtml(value) {
  const span = document.createElement('span');
  span.textContent = String(value ?? '');
  return span.innerHTML;
}

function getCombatStatus() {
  const playerLost = combatState.player.team.every((card) => card.defeated);
  const enemyLost = combatState.enemy.team.every((card) => card.defeated);

  if (playerLost) {
    return 'defeat';
  }

  if (enemyLost) {
    return 'victory';
  }

  return 'playing';
}

function updateMessage(message, error = false) {
  const messageElement = document.querySelector('#combat-message');

  if (!messageElement) {
    return;
  }

  messageElement.textContent = message;
  messageElement.className = error
    ? 'mt-4 min-h-6 text-sm text-red-300'
    : 'mt-4 min-h-6 text-sm text-stone-400';
}

function getCardClasses(card, index, playerCard) {
  const isActive = playerCard
    ? index === combatState.player_active
    : index === combatState.enemy_active;
  const isTarget = playerCard
    ? index === selectedPlayerIndex
    : index === selectedEnemyIndex;
  const defeatedClass = card.defeated ? 'is-defeated' : '';
  const activeClass = isActive ? 'is-active' : '';
  const targetClass = isTarget ? 'is-target' : '';

  return `${activeClass} ${targetClass} ${defeatedClass}`;
}

function renderCard(card, index, playerCard) {
  const healthPercent = Math.round((card.hp / card.max_hp) * 100);
  const side = playerCard ? 'player' : 'enemy';
  const cardClasses = getCardClasses(card, index, playerCard);
  const title = escapeHtml(card.title);
  const poster = card.poster
    ? `<img src="${escapeHtml(card.poster)}" alt="">`
    : `<span>${escapeHtml(card.title.slice(0, 2).toUpperCase())}</span>`;

  return `
    <article
      class="combat-unit ${cardClasses}"
      data-target-side="${side}"
      data-target-index="${index}"
      tabindex="${card.defeated ? '-1' : '0'}"
    >
      <div class="combat-unit__poster">${poster}</div>
      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold">${title}</p>
        <p class="mt-0.5 truncate text-xs text-stone-500">
          ${escapeHtml(card.genre)} · ${escapeHtml(card.passive.name)}
        </p>
        <p class="mt-1 truncate text-[10px] text-emerald-300/70">
          Fort : ${escapeHtml(card.strengths.join(', '))}
        </p>
        <p class="truncate text-[10px] text-red-300/70">
          Faible : ${escapeHtml(card.weaknesses.join(', '))}
        </p>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10">
          <span class="block h-full rounded-full bg-mint" style="width: ${healthPercent}%"></span>
        </div>
        <p class="mt-1 text-[10px] text-stone-500">${card.hp} / ${card.max_hp} PV</p>
      </div>
    </article>
  `;
}

function canUseAction(card, cost) {
  return combatState.turn === 'player'
    && combatState.player.energy >= cost
    && !card.defeated;
}

function renderAttackButtons(card, cardIndex) {
  return card.attacks.map((attack, attackIndex) => {
    const disabled = canUseAction(card, attack.cost) ? '' : 'disabled';
    const damage = attack.damage || 'Effet';

    return `
      <button
        type="button"
        class="action-button"
        data-attack-index="${attackIndex}"
        data-attacker-index="${cardIndex}"
        ${disabled}
      >
        <span>${escapeHtml(attack.name)}</span>
        <small>${damage} · ${attack.cost} énergie</small>
      </button>
    `;
  }).join('');
}

function renderEffectButton(card, cardIndex) {
  const disabled = canUseAction(card, card.effect.cost) ? '' : 'disabled';

  return `
    <button
      type="button"
      class="action-button action-button--effect"
      data-effect-index="${cardIndex}"
      ${disabled}
    >
      <span>${escapeHtml(card.effect.name)}</span>
      <small>${escapeHtml(card.effect.description)} · ${card.effect.cost} énergie</small>
    </button>
  `;
}

function renderPlayerCard(card, index) {
  return `
    <div class="combat-player-unit">
      ${renderCard(card, index, true)}
      <div class="combat-player-unit__actions">
        ${renderAttackButtons(card, index)}
        ${renderEffectButton(card, index)}
      </div>
    </div>
  `;
}

function renderCombatLog() {
  const log = document.querySelector('#combat-log');
  log.innerHTML = combatState.log.slice().reverse().map((entry) => `
    <li class="border-b border-white/5 pb-2">${escapeHtml(entry)}</li>
  `).join('');
}

function selectTarget(cardElement) {
  const side = cardElement.dataset.targetSide;
  const index = Number(cardElement.dataset.targetIndex);

  if (side === 'enemy') {
    selectedEnemyIndex = index;
  } else {
    selectedPlayerIndex = index;
  }

  renderCombatState();
}

function addTargetListeners() {
  document.querySelectorAll('[data-target-side]').forEach((card) => {
    card.addEventListener('click', () => selectTarget(card));
  });
}

function addActionListeners() {
  document.querySelectorAll('[data-attack-index]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      sendCombatAction('attack', {
        attack_index: Number(button.dataset.attackIndex),
        attacker_index: Number(button.dataset.attackerIndex),
        target_index: selectedEnemyIndex,
      });
    });
  });

  document.querySelectorAll('[data-effect-index]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();

      const sourceIndex = Number(button.dataset.effectIndex);
      const effect = combatState.player.team[sourceIndex].effect;
      const supportEffect = ['heal', 'shield'].includes(effect.type);
      const targetSide = supportEffect ? 'player' : 'enemy';
      const targetIndex = supportEffect ? selectedPlayerIndex : selectedEnemyIndex;

      sendCombatAction('effect', {
        source_index: sourceIndex,
        target_side: targetSide,
        target_index: targetIndex,
      });
    });
  });
}

function updateCombatControls(status) {
  const endTurnButton = document.querySelector('#end-turn');
  const focusButton = document.querySelector('#focus-energy');
  const playerTurn = combatState.turn === 'player';

  endTurnButton.disabled = !playerTurn || status !== 'playing';
  focusButton.disabled = !playerTurn || combatState.focus_used || status !== 'playing';
}

function showCombatResult(status) {
  if (status === 'playing') {
    return;
  }

  const result = document.querySelector('#combat-result');
  const title = document.querySelector('#combat-result-title');
  const text = document.querySelector('#combat-result-text');

  result.classList.remove('hidden');
  title.textContent = status === 'victory' ? 'Victoire !' : 'Défaite';
  text.textContent = status === 'victory'
    ? 'Les quatre cartes adverses sont vaincues.'
    : 'Votre équipe a été entièrement vaincue.';
}

function renderCombatState() {
  if (combatState.enemy.team[selectedEnemyIndex]?.defeated) {
    selectedEnemyIndex = combatState.enemy_active;
  }

  if (combatState.player.team[selectedPlayerIndex]?.defeated) {
    selectedPlayerIndex = combatState.player_active;
  }

  document.querySelector('#player-team').innerHTML = combatState.player.team
    .map((card, index) => renderPlayerCard(card, index))
    .join('');
  document.querySelector('#enemy-team').innerHTML = combatState.enemy.team
    .map((card, index) => renderCard(card, index, false))
    .join('');

  document.querySelector('#combat-energy').textContent =
    `${combatState.player.energy} / 12 énergie`;
  document.querySelector('#combat-turn-label').textContent =
    combatState.turn === 'player' ? 'VOTRE TOUR' : 'TOUR ADVERSE';
  document.querySelector('.combat-turn').classList.toggle(
    'is-enemy',
    combatState.turn !== 'player',
  );

  renderCombatLog();
  addTargetListeners();
  addActionListeners();

  const status = getCombatStatus();
  updateCombatControls(status);
  showCombatResult(status);
}

function disableCombatButtons() {
  document.querySelectorAll('.combat-field button, #end-turn, #focus-energy')
    .forEach((button) => {
      button.disabled = true;
    });
}

async function sendCombatAction(action, data = {}) {
  disableCombatButtons();

  try {
    const response = await fetch('../combat.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
      },
      body: JSON.stringify({ action, ...data }),
    });
    const result = await response.json();

    if (!result.success) {
      updateMessage(result.message || 'Action impossible.', true);
      renderCombatState();
      return;
    }

    if (result.state) {
      combatState = result.state;
      renderCombatState();
      updateMessage(
        result.result?.message
          || (combatState.turn === 'player' ? 'À vous de jouer.' : 'Le système joue...'),
      );
    }
  } catch (error) {
    updateMessage('Le serveur est indisponible.', true);
    renderCombatState();
  }
}

function updateTeamSelection(card) {
  const movieId = Number(card.dataset.movieId);
  const alreadySelected = selectedMovieIds.includes(movieId);

  if (alreadySelected) {
    selectedMovieIds = selectedMovieIds.filter((id) => id !== movieId);
  } else if (selectedMovieIds.length < 4) {
    selectedMovieIds.push(movieId);
  }

  card.setAttribute('aria-pressed', String(selectedMovieIds.includes(movieId)));
  teamCounter.textContent = `${selectedMovieIds.length} / 4 cartes`;
  startCombatButton.disabled = selectedMovieIds.length !== 4;
  teamMessage.textContent = selectedMovieIds.length === 4
    ? 'Équipe prête.'
    : 'Choisissez exactement 4 cartes.';
}

async function startCombat() {
  startCombatButton.disabled = true;

  try {
    const response = await fetch('../combat.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
      },
      body: JSON.stringify({
        action: 'start',
        movie_ids: selectedMovieIds,
      }),
    });
    const result = await response.json();

    if (!result.success) {
      teamMessage.textContent = result.message;
      startCombatButton.disabled = false;
      return;
    }

    combatState = result.state;
    selectedEnemyIndex = combatState.enemy_active;
    selectedPlayerIndex = combatState.player_active;
    teamBuilder.classList.add('hidden');
    combatBoard.classList.remove('hidden');
    renderCombatState();
  } catch (error) {
    teamMessage.textContent = 'Le serveur est indisponible.';
    startCombatButton.disabled = false;
  }
}

availableCards.forEach((card) => {
  card.addEventListener('click', () => updateTeamSelection(card));
});

if (startCombatButton) {
  startCombatButton.addEventListener('click', startCombat);
}

document.querySelector('#end-turn')?.addEventListener('click', () => {
  sendCombatAction('end_turn');
});

document.querySelector('#focus-energy')?.addEventListener('click', () => {
  sendCombatAction('focus');
});

document.querySelector('#new-combat')?.addEventListener('click', () => {
  window.location.reload();
});
