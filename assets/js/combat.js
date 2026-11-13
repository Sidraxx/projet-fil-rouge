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
    ? `<img data-poster-fallback src="${escapeHtml(card.poster)}" alt="">`
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
        aria-label="${escapeHtml(attack.name)} : ${damage}, coûte ${attack.cost} énergie"
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
      aria-label="${escapeHtml(card.effect.name)} : ${escapeHtml(card.effect.description)}, coûte ${card.effect.cost} énergie"
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

function findCombatCard(side, index) {
  return document.querySelector(
    `[data-target-side="${side}"][data-target-index="${index}"]`,
  );
}

function addAnimationClass(element, className) {
  if (!element) {
    return;
  }

  element.classList.remove(className);
  void element.offsetWidth;
  element.classList.add(className);
}

function showFloatingValue(side, index, value, type) {
  const card = findCombatCard(side, index);
  if (!card || value === 0) {
    return;
  }

  const valueElement = document.createElement('span');
  valueElement.className = `combat-floating-value combat-floating-value--${type}`;
  valueElement.textContent = `${value > 0 ? '+' : ''}${value}`;
  card.appendChild(valueElement);
  valueElement.addEventListener('animationend', () => valueElement.remove(), { once: true });
}

function animateStateChanges(previousState, nextState, action, data) {
  if (!previousState) {
    return;
  }

  ['player', 'enemy'].forEach((side) => {
    nextState[side].team.forEach((card, index) => {
      const previousCard = previousState[side].team[index];
      const cardElement = findCombatCard(side, index);

      if (!previousCard || !cardElement) {
        return;
      }

      const healthChange = card.hp - previousCard.hp;
      const shieldChange = card.shield - previousCard.shield;

      if (healthChange < 0) {
        addAnimationClass(cardElement, 'is-damaged');
        showFloatingValue(side, index, healthChange, 'damage');
      } else if (healthChange > 0) {
        addAnimationClass(cardElement, 'is-healed');
        showFloatingValue(side, index, healthChange, 'heal');
      }

      if (shieldChange > 0) {
        addAnimationClass(cardElement, 'is-shielded');
        showFloatingValue(side, index, shieldChange, 'shield');
      }

      if (!previousCard.defeated && card.defeated) {
        addAnimationClass(cardElement, 'is-defeated-now');
      }
    });
  });

  if (action === 'attack') {
    addAnimationClass(
      findCombatCard('player', Number(data.attacker_index)),
      'is-attacking',
    );
  }

  if (action === 'effect') {
    addAnimationClass(
      findCombatCard('player', Number(data.source_index)),
      'is-casting',
    );
  }

  if (action === 'focus') {
    addAnimationClass(document.querySelector('.combat-resource--energy'), 'is-gaining');
  }

  if (previousState.turn !== nextState.turn) {
    addAnimationClass(document.querySelector('.combat-turn'), 'turn-changed');
  }
}

function updateTargetSummary() {
  const summary = document.querySelector('#combat-target-summary');
  const enemy = combatState.enemy.team[selectedEnemyIndex];
  const player = combatState.player.team[selectedPlayerIndex];

  if (!summary || !enemy || !player) {
    return;
  }

  summary.textContent = `Cible adverse : ${enemy.title} · Cible alliée : ${player.title}`;
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
  const icon = document.querySelector('#combat-result-icon');
  const label = document.querySelector('#combat-result-label');
  const title = document.querySelector('#combat-result-title');
  const text = document.querySelector('#combat-result-text');

  result.classList.remove('combat-result--victory', 'combat-result--defeat');
  result.classList.add(`combat-result--${status}`);
  result.classList.remove('hidden');
  icon.textContent = status === 'victory' ? '★' : '✕';
  label.textContent = status === 'victory' ? 'Duel remporté' : 'Fin du duel';
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
  updateTargetSummary();
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
  const previousState = combatState;

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
      animateStateChanges(previousState, combatState, action, data);
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

const rulesDialog = document.querySelector('#combat-rules');
const rulesButton = document.querySelector('#combat-rules-button');
const closeRulesButton = document.querySelector('#combat-rules-close');

rulesButton?.addEventListener('click', () => {
  rulesDialog.showModal();
});

closeRulesButton?.addEventListener('click', () => {
  rulesDialog.close();
});

rulesDialog?.addEventListener('click', (event) => {
  if (event.target === rulesDialog) {
    rulesDialog.close();
  }
});
