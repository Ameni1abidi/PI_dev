import React from 'react';
import { createRoot } from 'react-dom/client';
import Picker from '@emoji-mart/react';
import data from '@emoji-mart/data';

const REACTION_KEY = 'forum_comment_reactions_v1';
const QUICK_REACTIONS = ['👍', '❤️', '😂', '😮', '😢', '😡'];

function insertEmoji(textarea, emoji) {
  const value = textarea.value || '';
  const cursorStart = textarea.selectionStart ?? value.length;
  const cursorEnd = textarea.selectionEnd ?? value.length;
  const emojiChar = emoji?.native || '';

  textarea.value = value.slice(0, cursorStart) + emojiChar + value.slice(cursorEnd);

  const newCursor = cursorStart + emojiChar.length;
  textarea.focus();
  textarea.setSelectionRange(newCursor, newCursor);
  textarea.dispatchEvent(new Event('input', { bubbles: true }));
}

function readReactionsStore() {
  try {
    const raw = localStorage.getItem(REACTION_KEY);
    return raw ? JSON.parse(raw) : {};
  } catch (e) {
    return {};
  }
}

function writeReactionsStore(store) {
  localStorage.setItem(REACTION_KEY, JSON.stringify(store));
}

function getCommentReactionState(commentId) {
  const store = readReactionsStore();
  if (!store[commentId]) {
    store[commentId] = { counts: {}, my: null };
    writeReactionsStore(store);
  }
  return store[commentId];
}

function saveCommentReactionState(commentId, state) {
  const store = readReactionsStore();
  store[commentId] = state;
  writeReactionsStore(store);
}

function applyReaction(commentId, emoji) {
  const state = getCommentReactionState(commentId);
  const previous = state.my;

  if (previous) {
    state.counts[previous] = Math.max(0, (state.counts[previous] || 0) - 1);
    if (state.counts[previous] === 0) {
      delete state.counts[previous];
    }
  }

  if (previous === emoji) {
    state.my = null;
  } else {
    state.counts[emoji] = (state.counts[emoji] || 0) + 1;
    state.my = emoji;
  }

  saveCommentReactionState(commentId, state);
  return state;
}

function renderReactionSummary(container, state) {
  const summary = container.querySelector('.comment-reaction-summary');
  summary.innerHTML = '';

  const entries = Object.entries(state.counts).sort((a, b) => b[1] - a[1]);
  if (entries.length === 0) {
    summary.textContent = 'Aucune reaction';
    summary.style.opacity = '0.7';
    return;
  }

  summary.style.opacity = '1';
  entries.forEach(([emoji, count]) => {
    const chip = document.createElement('span');
    chip.textContent = `${emoji} ${count}`;
    chip.style.display = 'inline-flex';
    chip.style.alignItems = 'center';
    chip.style.gap = '4px';
    chip.style.padding = '3px 8px';
    chip.style.marginRight = '6px';
    chip.style.marginTop = '4px';
    chip.style.borderRadius = '999px';
    chip.style.background = '#f1f5f9';
    chip.style.fontSize = '13px';
    summary.appendChild(chip);
  });
}

function updateQuickReactionButtons(container, state) {
  const buttons = container.querySelectorAll('.comment-reaction-btn');
  buttons.forEach((button) => {
    const emoji = button.dataset.emoji;
    const isMine = state.my === emoji;
    button.style.background = isMine ? '#e2fbe8' : '#fff';
    button.style.borderColor = isMine ? '#34a853' : '#cbd5e1';
  });
}

function buildReactionPickerHost(container, commentId) {
  const pickerHost = document.createElement('div');
  pickerHost.style.display = 'none';
  pickerHost.style.position = 'absolute';
  pickerHost.style.zIndex = '2000';
  pickerHost.style.top = '38px';
  pickerHost.style.left = '0';
  pickerHost.style.boxShadow = '0 12px 30px rgba(0,0,0,0.18)';
  pickerHost.style.borderRadius = '12px';
  pickerHost.style.overflow = 'hidden';

  const root = createRoot(pickerHost);
  root.render(
    React.createElement(Picker, {
      data,
      locale: 'fr',
      theme: 'light',
      previewPosition: 'none',
      perLine: 8,
      onEmojiSelect: (emoji) => {
        const state = applyReaction(commentId, emoji?.native || '');
        renderReactionSummary(container, state);
        updateQuickReactionButtons(container, state);
        pickerHost.style.display = 'none';
      },
      onClickOutside: () => {
        pickerHost.style.display = 'none';
      },
    })
  );

  return pickerHost;
}

function mountReactions() {
  const reactionBlocks = document.querySelectorAll('.comment-reactions[data-comment-id]');
  reactionBlocks.forEach((container) => {
    const commentId = container.dataset.commentId;
    if (!commentId) {
      return;
    }

    container.style.marginTop = '8px';
    container.style.marginBottom = '8px';
    container.style.position = 'relative';

    const toolbar = document.createElement('div');
    toolbar.style.display = 'flex';
    toolbar.style.flexWrap = 'wrap';
    toolbar.style.gap = '6px';
    toolbar.style.alignItems = 'center';

    const label = document.createElement('span');
    label.textContent = 'Reagir:';
    label.style.fontSize = '13px';
    label.style.color = '#475569';
    toolbar.appendChild(label);

    QUICK_REACTIONS.forEach((emoji) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'comment-reaction-btn';
      button.dataset.emoji = emoji;
      button.textContent = emoji;
      button.style.border = '1px solid #cbd5e1';
      button.style.borderRadius = '999px';
      button.style.padding = '2px 8px';
      button.style.background = '#fff';
      button.style.cursor = 'pointer';
      button.addEventListener('click', () => {
        const state = applyReaction(commentId, emoji);
        renderReactionSummary(container, state);
        updateQuickReactionButtons(container, state);
      });
      toolbar.appendChild(button);
    });

    const moreBtn = document.createElement('button');
    moreBtn.type = 'button';
    moreBtn.textContent = '+';
    moreBtn.title = 'Plus de reactions';
    moreBtn.style.border = '1px solid #cbd5e1';
    moreBtn.style.borderRadius = '999px';
    moreBtn.style.padding = '2px 10px';
    moreBtn.style.background = '#fff';
    moreBtn.style.cursor = 'pointer';
    toolbar.appendChild(moreBtn);

    const summary = document.createElement('div');
    summary.className = 'comment-reaction-summary';
    summary.style.marginTop = '6px';

    const pickerHost = buildReactionPickerHost(container, commentId);
    moreBtn.addEventListener('click', () => {
      pickerHost.style.display = pickerHost.style.display === 'none' ? 'block' : 'none';
    });

    container.appendChild(toolbar);
    container.appendChild(summary);
    container.appendChild(pickerHost);

    const state = getCommentReactionState(commentId);
    renderReactionSummary(container, state);
    updateQuickReactionButtons(container, state);
  });
}

function mountPickerOnCommentForm(form) {
  const textarea = form.querySelector('textarea[name="contenu"]');
  if (!textarea) {
    return;
  }

  const wrapper = document.createElement('div');
  wrapper.className = 'emoji-toolbar';
  wrapper.style.position = 'relative';
  wrapper.style.margin = '8px 0';

  const toggleBtn = document.createElement('button');
  toggleBtn.type = 'button';
  toggleBtn.textContent = '😀 Emoji';
  toggleBtn.style.padding = '6px 10px';
  toggleBtn.style.borderRadius = '8px';
  toggleBtn.style.border = '1px solid #cbd5e1';
  toggleBtn.style.background = '#fff';
  toggleBtn.style.cursor = 'pointer';

  const pickerHost = document.createElement('div');
  pickerHost.style.display = 'none';
  pickerHost.style.position = 'absolute';
  pickerHost.style.zIndex = '2000';
  pickerHost.style.top = '42px';
  pickerHost.style.left = '0';
  pickerHost.style.boxShadow = '0 12px 30px rgba(0,0,0,0.18)';
  pickerHost.style.borderRadius = '12px';
  pickerHost.style.overflow = 'hidden';

  wrapper.appendChild(toggleBtn);
  wrapper.appendChild(pickerHost);

  form.insertBefore(wrapper, textarea);

  const root = createRoot(pickerHost);
  root.render(
    React.createElement(Picker, {
      data,
      locale: 'fr',
      theme: 'light',
      onEmojiSelect: (emoji) => {
        insertEmoji(textarea, emoji);
        pickerHost.style.display = 'none';
      },
      onClickOutside: () => {
        pickerHost.style.display = 'none';
      },
    })
  );

  toggleBtn.addEventListener('click', () => {
    pickerHost.style.display = pickerHost.style.display === 'none' ? 'block' : 'none';
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('.comment-form');
  forms.forEach((form) => mountPickerOnCommentForm(form));
  mountReactions();
});
