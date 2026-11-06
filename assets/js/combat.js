const builder = document.querySelector('#team-builder');
const board = document.querySelector('#combat-board');
const teamCards = [...document.querySelectorAll('.combat-card')];
const counter = document.querySelector('#team-counter');
const startButton = document.querySelector('#start-combat');
const teamMessage = document.querySelector('#team-message');
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
let selectedIds = [];
let state = null;
let selectedEnemyTarget = 0;
let selectedPlayerTarget = 0;

function escapeHtml(value) {
  const element = document.createElement('span');
  element.textContent = String(value ?? '');
  return element.innerHTML;
}

function showMessage(message, isError = false) {
  const element = document.querySelector('#combat-message');
  element.textContent = message;
  element.className = `mt-4 min-h-6 text-sm ${isError ? 'text-red-300' : 'text-stone-400'}`;
}

function cardMarkup(card, active, index, isPlayer) {
  const health = Math.round((card.hp / card.max_hp) * 100);
  const targetSelected = isPlayer ? selectedPlayerTarget === index : selectedEnemyTarget === index;
  return `<article data-target-side="${isPlayer ? 'player' : 'enemy'}" data-target-index="${index}" class="combat-unit ${active ? 'is-active' : ''} ${targetSelected ? 'is-target' : ''} ${card.defeated ? 'is-defeated' : ''}" tabindex="${card.defeated ? '-1' : '0'}">
    <div class="combat-unit__poster">${card.poster ? `<img src="${escapeHtml(card.poster)}" alt="">` : `<span>${escapeHtml(card.title.slice(0, 2).toUpperCase())}</span>`}</div>
    <div class="min-w-0 flex-1">
      <p class="truncate text-sm font-semibold">${escapeHtml(card.title)}</p>
      <p class="mt-0.5 truncate text-xs text-stone-500">${escapeHtml(card.genre)} · ${escapeHtml(card.passive.name)}</p>
      <p class="mt-1 truncate text-[10px] text-emerald-300/70">Fort : ${escapeHtml(card.strengths.join(', '))}</p>
      <p class="truncate text-[10px] text-red-300/70">Faible : ${escapeHtml(card.weaknesses.join(', '))}</p>
      <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10"><span class="block h-full rounded-full bg-mint" style="width:${health}%"></span></div>
      <p class="mt-1 text-[10px] text-stone-500">${card.hp} / ${card.max_hp} PV</p>
    </div>
  </article>`;
}

function playerCardMarkup(card, active, index) {
  const attacks = card.attacks.map((attack, attackIndex) => `
    <button type="button" data-attack="${attackIndex}" data-attacker-index="${index}" class="action-button" ${state.turn !== 'player' || state.player.energy < attack.cost || card.defeated ? 'disabled' : ''}>
      <span>${escapeHtml(attack.name)}</span>
      <small>${attack.damage || 'Effet'} · ${attack.cost} énergie</small>
    </button>`).join('');
  const effect = `
    <button type="button" data-effect data-effect-index="${index}" class="action-button action-button--effect" ${state.turn !== 'player' || state.player.energy < card.effect.cost || card.defeated ? 'disabled' : ''}>
      <span>${escapeHtml(card.effect.name)}</span>
      <small>${escapeHtml(card.effect.description)} · ${card.effect.cost} énergie</small>
    </button>`;

  return `<div class="combat-player-unit">
    ${cardMarkup(card, active, index, true)}
    <div class="combat-player-unit__actions">${attacks}${effect}</div>
  </div>`;
}

function renderState() {
  const playerTeam = document.querySelector('#player-team');
  const enemyTeam = document.querySelector('#enemy-team');
  if (state.enemy.team[selectedEnemyTarget]?.defeated) {
    selectedEnemyTarget = state.enemy_active;
  }
  if (state.player.team[selectedPlayerTarget]?.defeated) {
    selectedPlayerTarget = state.player_active;
  }
  playerTeam.innerHTML = state.player.team.map((card, index) => playerCardMarkup(card, index === state.player_active, index)).join('');
  enemyTeam.innerHTML = state.enemy.team.map((card, index) => cardMarkup(card, index === state.enemy_active, index, false)).join('');
  document.querySelector('#combat-energy').textContent = `${state.player.energy} / 12 énergie`;
  document.querySelector('#combat-log').innerHTML = state.log.slice().reverse().map((entry) => `<li class="border-b border-white/5 pb-2">${escapeHtml(entry)}</li>`).join('');

  document.querySelectorAll('[data-target-side]').forEach((card) => {
    card.addEventListener('click', () => {
      const index = Number(card.dataset.targetIndex);
      if (card.dataset.targetSide === 'enemy') {
        selectedEnemyTarget = index;
      } else {
        selectedPlayerTarget = index;
      }
      renderState();
    });
  });
  document.querySelectorAll('[data-attack]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      sendAction('attack', {
        attack_index: Number(button.dataset.attack),
        attacker_index: Number(button.dataset.attackerIndex),
        target_index: selectedEnemyTarget,
      });
    });
  });
  document.querySelectorAll('[data-effect]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const index = Number(button.dataset.effectIndex);
      const effect = state.player.team[index].effect;
      const targetSide = ['heal', 'shield'].includes(effect.type) ? 'player' : 'enemy';
      sendAction('effect', {
        source_index: index,
        target_side: targetSide,
        target_index: targetSide === 'player' ? selectedPlayerTarget : selectedEnemyTarget,
      });
    });
  });

  const status = state.player.team.every((card) => card.defeated)
    ? 'defeat'
    : state.enemy.team.every((card) => card.defeated) ? 'victory' : 'playing';
  document.querySelector('#end-turn').disabled = state.turn !== 'player' || status !== 'playing';
  const focusButton = document.querySelector('#focus-energy');
  focusButton.disabled = state.turn !== 'player' || state.focus_used || status !== 'playing';
  if (status !== 'playing') {
    document.querySelector('#combat-result').classList.remove('hidden');
    document.querySelector('#combat-result-title').textContent = status === 'victory' ? 'Victoire !' : 'Défaite';
    document.querySelector('#combat-result-text').textContent = status === 'victory' ? 'Les quatre cartes adverses sont vaincues.' : 'Votre équipe a été entièrement vaincue.';
  }
}

async function sendAction(action, data = {}) {
  document.querySelectorAll('.combat-field button, #end-turn, #focus-energy').forEach((button) => { button.disabled = true; });
  let stateUpdated = false;
  try {
    const response = await fetch('../combat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify({ action, ...data }),
    });
    const result = await response.json();
    if (!result.success) {
      showMessage(result.message || 'Action impossible.', true);
      return;
    }
    if (result.state) {
      state = result.state;
      stateUpdated = true;
      renderState();
      showMessage(result.result?.message || (state.turn === 'player' ? 'À vous de jouer.' : 'Le système joue...'));
    }
  } catch (error) {
    showMessage('Le serveur est indisponible.', true);
  } finally {
    if (!stateUpdated && state) {
      renderState();
    }
  }
}

teamCards.forEach((card) => {
  card.addEventListener('click', () => {
    const id = Number(card.dataset.movieId);
    if (selectedIds.includes(id)) {
      selectedIds = selectedIds.filter((selectedId) => selectedId !== id);
    } else if (selectedIds.length < 4) {
      selectedIds.push(id);
    }
    card.setAttribute('aria-pressed', String(selectedIds.includes(id)));
    counter.textContent = `${selectedIds.length} / 4 cartes`;
    startButton.disabled = selectedIds.length !== 4;
    teamMessage.textContent = selectedIds.length === 4 ? 'Équipe prête.' : 'Choisissez exactement 4 cartes.';
  });
});

startButton?.addEventListener('click', async () => {
  startButton.disabled = true;
  try {
    const response = await fetch('../combat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify({ action: 'start', movie_ids: selectedIds }),
    });
    const result = await response.json();
    if (!result.success) {
      teamMessage.textContent = result.message;
      startButton.disabled = false;
      return;
    }
    state = result.state;
    selectedEnemyTarget = state.enemy_active;
    selectedPlayerTarget = state.player_active;
    builder.classList.add('hidden');
    board.classList.remove('hidden');
    renderState();
  } catch (error) {
    teamMessage.textContent = 'Le serveur est indisponible.';
    startButton.disabled = false;
  }
});

document.querySelector('#end-turn')?.addEventListener('click', () => sendAction('end_turn'));
document.querySelector('#focus-energy')?.addEventListener('click', () => sendAction('focus'));
document.querySelector('#new-combat')?.addEventListener('click', () => window.location.reload());
