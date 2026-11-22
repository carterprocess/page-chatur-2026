// Testes de logica pura (sem DOM): node --test tests/
import test from 'node:test';
import assert from 'node:assert/strict';

globalThis.localStorage = { getItem: () => null, setItem: () => {} };

const { createStore } = await import('../assets/js/store.js');
const { createActions } = await import('../assets/js/actions.js');
const { buildParams } = await import('../assets/js/api.js');
const { DEFAULT_FILTERS } = await import('../assets/js/config.js');

const model = (username, extra = {}) => ({
  username, embed_src: 'https://chaturbate.com/in/?room=' + username, is_favorite: false, num_users: 1, ...extra
});

function setup(models) {
  const store = createStore();
  store.set({ models });
  const notes = [];
  return { store, notes, actions: createActions(store, (m) => notes.push(m)) };
}

test('selectModel preenche o slot ativo e avanca para o proximo vazio', () => {
  const { store, actions } = setup([model('a'), model('b')]);
  actions.selectModel('a');
  assert.equal(store.get().slots[0].username, 'a');
  assert.equal(store.get().activeSlot, 1);
  actions.selectModel('b');
  assert.equal(store.get().slots[1].username, 'b');
});

test('selectModel nao duplica o mesmo perfil', () => {
  const { store, actions, notes } = setup([model('a')]);
  actions.selectModel('a');
  actions.selectModel('A');
  assert.equal(store.get().slots.filter((s) => s.username).length, 1);
  assert.equal(store.get().activeSlot, 0);
  assert.match(notes.at(-1), /ja esta/);
});

test('setLayout limita o slot ativo e fillFromFavorites respeita o layout', () => {
  const { store, actions } = setup([model('a', { is_favorite: true }), model('b', { is_favorite: true }), model('c', { is_favorite: true })]);
  actions.setLayout(2);
  actions.fillFromFavorites();
  assert.deepEqual(store.get().slots.map((s) => s.username), ['a', 'b', '', '']);
});

test('clearSlot esvazia e seleciona o slot', () => {
  const { store, actions } = setup([model('a')]);
  actions.selectModel('a');
  actions.clearSlot(0);
  assert.equal(store.get().slots[0].src, '');
  assert.equal(store.get().activeSlot, 0);
});

test('buildParams so envia filtros restritivos', () => {
  const p = buildParams({ ...DEFAULT_FILTERS, maxAge: '99', favorites: true, region: ['asia'] });
  assert.equal(p.get('maxAge'), null);
  assert.equal(p.get('favorites'), '1');
  assert.deepEqual(p.getAll('region'), ['asia']);
  assert.equal(buildParams({ ...DEFAULT_FILTERS, maxAge: '30' }).get('maxAge'), '30');
});
