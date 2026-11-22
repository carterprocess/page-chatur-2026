import { MAX_SLOTS } from './config.js';
import { sameModel } from './util.js';

const emptySlot = () => ({ username: '', src: '' });

/** Operacoes de negocio sobre o estado (sem tocar no DOM). */
export function createActions(store, notify) {
  const visibleCount = () => store.get().layout;

  function nextEmptyAfter(index) {
    const { slots } = store.get();
    for (let step = 1; step <= visibleCount(); step += 1) {
      const i = (index + step) % visibleCount();
      if (!slots[i].src) return i;
    }
    return (index + 1) % visibleCount(); // todos cheios: o proximo clique substitui o seguinte
  }

  function setActive(index) {
    if (index >= 0 && index < visibleCount()) store.set({ activeSlot: index });
  }

  function placeModel(model) {
    const { slots, activeSlot } = store.get();
    const next = slots.slice();
    next[activeSlot] = { username: model.username, src: model.embed_src };
    store.set({ slots: next });
    return activeSlot;
  }

  function selectModel(username) {
    const { models, slots } = store.get();
    const model = models.find((m) => sameModel(m.username, username));
    if (!model || !model.embed_src) return;

    const existing = slots.findIndex((s, i) => i < visibleCount() && sameModel(s.username, model.username));
    if (existing >= 0) {
      setActive(existing);
      notify(model.username + ' ja esta no player ' + (existing + 1));
      return;
    }

    const used = placeModel(model);
    store.set({ activeSlot: nextEmptyAfter(used) });
    notify(model.username + ' no player ' + (used + 1));
  }

  function clearSlot(index) {
    const next = store.get().slots.slice();
    next[index] = emptySlot();
    store.set({ slots: next, activeSlot: index });
  }

  function toggleMaximize(index) {
    store.set({ maximized: store.get().maximized === index ? -1 : index });
  }

  function setLayout(layout) {
    const { activeSlot } = store.get();
    store.set({ layout, maximized: -1, activeSlot: Math.min(activeSlot, layout - 1) });
  }

  // Preenche os players vazios visiveis com os favoritos online de mais views.
  function fillFromFavorites() {
    const { models, slots } = store.get();
    const open = new Set(slots.map((s) => s.username.toLowerCase()).filter(Boolean));
    const candidates = models.filter((m) => m.is_favorite && !open.has(m.username.toLowerCase()));
    const next = slots.slice();
    let filled = 0;
    for (let i = 0; i < Math.min(visibleCount(), MAX_SLOTS) && candidates.length; i += 1) {
      if (!next[i].src) {
        const m = candidates.shift();
        next[i] = { username: m.username, src: m.embed_src };
        filled += 1;
      }
    }
    store.set({ slots: next });
    notify(filled ? filled + ' favorito(s) adicionados' : 'Sem favoritos online disponiveis ou players cheios');
  }

  return { selectModel, setActive, clearSlot, toggleMaximize, setLayout, fillFromFavorites };
}
