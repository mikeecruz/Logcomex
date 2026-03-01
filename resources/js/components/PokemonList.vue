<template>
  <div class="page">
    <header class="topbar">
      <h1>Logcomex</h1>

      <div class="actions">
        <div>
            <input
              v-model="search"
              class="input"
              placeholder="Buscar por nome…"
              autocomplete="off"
            />
        </div>

        <div>
            <Multiselect
                v-model="typeFilter"
                :options="allTypes"
                label="label"
                track-by="value"
                placeholder="Filtrar por tipo"
                />
        </div>

        <button class="btn" :disabled="isUpdating" @click="runImport">
          {{ isUpdating ? "Atualizando..." : "Atualizar importação" }}
        </button>

        <div v-if="isUpdating" class="banner">Importanto <span>...</span></div>
      </div>
    </header>


    <section class="grid" v-if="filtered.length">
      <article v-for="p in filtered" :key="p.poke_id" class="card">
        <div class="card-head">
          <Maximize2 class="iconbtn" @click="openDetails(p)" />
        </div>

        <div class="media">
          <img
            v-if="p.dream_world_svg"
            :src="p.dream_world_svg"
            :alt="p.name"
            class="img"
          />
          <div v-else class="img-placeholder">sem imagem</div>
        </div>

        <h3 class="title">{{ capitalize(p.name) }}</h3>

        <div class="types">
          <span v-for="t in p.types" :key="t" class="chip">
            {{ t }}
          </span>
        </div>

        <div class="stats">
          <div class="stat">
            <div class="label">Altura</div>
            <div class="value">{{ formatHeight(p.height) }}</div>
          </div>
          <div class="stat">
            <div class="label">Peso</div>
            <div class="value">{{ formatWeight(p.weight) }}</div>
          </div>
        </div>
      </article>
    </section>

    <section v-else class="empty">
        <div class="empty-card">
            <strong>Nenhum Pokémon encontrado</strong>
            <p>Tente ajustar os filtros ou pesquisar por outro nome.</p>
        </div>
    </section>

    <footer class="pager" v-if="meta && meta.last_page > 1">
        <div class="pager-left">
            <span class="muted">
            Página <strong>{{ meta.current_page }}</strong> de <strong>{{ meta.last_page }}</strong>
            - Total <strong>{{ meta.total }}</strong>
            </span>
        </div>

        <div class="pager-right">
            <button class="btn-lite" :disabled="isLoading || page <= 1" @click="goToPage(1)">
                <ChevronsLeft />
            </button>

            <button class="btn-lite" :disabled="isLoading || page <= 1" @click="goToPage(page - 1)">
                <ChevronLeft />
            </button>

            <div class="page-numbers">
            <button
                v-for="p in pageButtons"
                :key="p"
                class="btn-page"
                :class="{ active: p === page }"
                :disabled="isLoading"
                @click="goToPage(p)"
            >
                {{ p }}
            </button>
            </div>

            <button class="btn-lite" :disabled="isLoading || page >= meta.last_page" @click="goToPage(page + 1)">
                <ChevronRight />
            </button>

            <button class="btn-lite" :disabled="isLoading || page >= meta.last_page" @click="goToPage(meta.last_page)">
                <ChevronsRight />
            </button>

            <div class="perpage">
            <span class="muted">Por página</span>
            <select class="input small" v-model.number="perPage">
                <option :value="12">12</option>
                <option :value="24">24</option>
                <option :value="48">48</option>
                <option :value="96">96</option>
            </select>
            </div>
        </div>
    </footer>

    <div v-if="selected" class="modal-overlay" @click.self="selected = null">
      <div class="modal">
        <div class="modal-head">
          <h2>{{ capitalize(selected.name) }} (#{{ selected.poke_id }})</h2>
          <button class="iconbtn" @click="selected = null">✕</button>
        </div>

        <div class="modal-body">
          <img
            v-if="selected.dream_world_svg"
            :src="selected.dream_world_svg"
            :alt="selected.name"
            class="img-lg"
          />

          <div class="row">
            <div><strong>Tipos:</strong>
                {{ selected.types
                    .map(t => t.charAt(0).toUpperCase() + t.slice(1))
                    .join(", ")
                }}
            </div>
            <div><strong>Altura:</strong> {{ formatHeight(selected.height) }}</div>
            <div><strong>Peso:</strong> {{ formatWeight(selected.weight) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
    import { computed, onMounted, ref, watch } from "vue";
    import axios from "axios";
    import { Maximize2, ChevronRight, ChevronLeft, ChevronsRight, ChevronsLeft } from "lucide-vue-next";
    import Multiselect from "@vueform/multiselect";
    import "@vueform/multiselect/themes/default.css";

    const search = ref("");
    const typeFilter = ref(null);
    const isUpdating = ref(false);
    const isLoading = ref(false);
    const selected = ref(null);
    const pokemons = ref([]);
    const page = ref(1);
    const perPage = ref(24);
    const meta = ref({
        current_page: 1,
        last_page: 1,
        total: 0,
    });

    const pageButtons = computed(() => {
        if (!meta.value) return [];

        const current = meta.value.current_page ?? 1;
        const last = meta.value.last_page ?? 1;
        const delta = 2;
        const start = Math.max(1, current - delta);
        const end = Math.min(last, current + delta);
        const buttons = [];

        for (let p = start; p <= end; p++) buttons.push(p);

        return buttons;
    });

    function goToPage(p) {
        if (!meta.value) return;
        const last = meta.value.last_page ?? 1;

        const next = Math.min(Math.max(1, p), last);
        if (next === page.value) return;

        page.value = next;
        loadPokemons();
    }

    async function loadPokemons() {
        isLoading.value = true;

        const { data } = await axios.get("/api/pokemons", {
            params: {
                search: search.value || undefined,
                type: typeFilter.value || undefined,
                page: page.value,
                per_page: perPage.value,
            },
        });

        pokemons.value = data.data ?? [];
        meta.value = {
            current_page: data.current_page ?? 1,
            last_page: data.last_page ?? 1,
            total: data.total ?? 0,
        };

        isLoading.value = false;
    }

    async function runImport() {
        isUpdating.value = true;
        await axios.get("/api/feeds");
        isUpdating.value = false;
        page.value = 1;
        await loadPokemons();
    }

    onMounted(loadPokemons);

    watch([search, typeFilter], () => {
        page.value = 1;
        loadPokemons();
    });

    const allTypes = computed(() => {
        const set = new Set();

        pokemons.value.forEach((p) => p.types.forEach((t) => set.add(t)));

        return Array.from(set)
            .sort()
            .map((t) => ({
                value: t,
                label: t.charAt(0).toUpperCase() + t.slice(1),
            })
        );
    });

    const filtered = computed(() => {
        return pokemons.value.filter((p) => {
            const matchesType = !typeFilter.value || p.types.includes(typeFilter.value);
            const matchesName =
            !search.value ||
            p.name.toLowerCase().includes(search.value.trim().toLowerCase());

            return matchesType && matchesName;
        });
    });

    function capitalize(s) {
        return s ? s.charAt(0).toUpperCase() + s.slice(1) : "";
    }

    function formatHeight(dm) {
        if (dm == null) return "—";
        return `${(dm / 10).toFixed(1)} m`;
    }

    function formatWeight(hg) {
        if (hg == null) return "—";
        return `${(hg / 10).toFixed(1)} kg`;
    }

    function openDetails(p) {
        selected.value = p;
    }
</script>

<style scoped>

    .empty {
        display: grid;
        place-items: center;
        padding: 32px 12px;
    }

    .empty-card {
        width: min(520px, 100%);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 16px;
        padding: 18px 16px;
        background: rgba(255,255,255,.8);
        text-align: center;
    }

    .empty-card p {
        margin: 8px 0 0;
        opacity: .85;
    }

    .pager {
        margin-top: 16px;
        padding: 12px;
        border: 1px solid #eee;
        border-radius: 14px;
        background: rgba(255,255,255,.75);
        backdrop-filter: blur(6px);
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .pager-right {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .btn-lite {
        padding: 8px 10px;
        border: 1px solid #ddd;
        background: #fff;
        border-radius: 10px;
        cursor: pointer;
    }

    .btn-lite:disabled {
        opacity: .55;
        cursor: not-allowed;
    }

    .btn-lite svg {
        width: 16px;
        height: 16px;
    }

    .page-numbers {
        display: flex;
        gap: 6px;
    }

    .btn-page {
        width: 38px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid #ddd;
        background: #fff;
        cursor: pointer;
    }

    .btn-page.active {
        border-color: #222;
        font-weight: 700;
    }

    .perpage {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-left: 6px;
    }

    .input.small {
        min-width: 90px;
        padding: 8px 10px;
    }

    .multiselect {
        min-width: 240px;
    }

    :deep(.multiselect-option.is-selected) {
        background: #b52d2d !important;
        color: white;
    }

    h1 {
        margin: 0;
        font-weight: bold;
        font-size: 30px;
        font-family: 'Roboto', sans-serif;
    }
    .page {
        padding: 20px;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial;
    }

    .topbar {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .input {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        line-height: 40px;
        height: 40px;
        min-width: 220px;
        background-color: #fff;
        font-family: 'Roboto', sans-serif;
    }

    .btn {
        padding: 10px 14px;
        border: 1px solid #777;
        border-radius: 10px;
        background: #777;
        color: #fff;
        font-family: "Roboto", sans-serif;
        cursor: pointer;
    }

    .btn:disabled {
        opacity: .6;
        cursor: not-allowed;
    }

    .banner {
        padding: 10px 12px;
        background: #8bddc3;
        border: 1px solid #0c9e70;
        border-radius: 12px;
        color: #024a34;
    }

    .banner span {
        display: inline-block;
        width: 1.6em;
        text-align: left;
    }

    .banner span::before {
        content: "";
        animation: dots 1.2s steps(4, end) infinite;
    }

    @keyframes dots {
        0%   { content: ""; }
        25%  { content: "."; }
        50%  { content: ".."; }
        75%  { content: "..."; }
        100% { content: ""; }
    }

    .grid {
        margin-top: 16px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 14px;
    }

    .card {
        border: 1px solid #eee;
        border-radius: 16px;
        padding: 12px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .04);
    }

    .card-head {
        display: flex;
        justify-content: end;
        align-items: center;
    }

    .iconbtn {
        border: none;
        background: transparent;
        cursor: pointer;
        padding: 6px;
        border-radius: 8px;
        transition: background 0.2s ease, transform 0.1s ease;
    }

    .iconbtn:hover {
        background: #f0f0f0;
        transform: scale(1.05);
    }

    .media {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 120px;
        margin: 10px 0;
    }

    .img {
        max-height: 110px;
        max-width: 100%;
    }

    .img-placeholder {
        width: 100%;
        height: 90px;
        border-radius: 12px;
        background: #f6f6f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 12px;
    }

    .title {
        margin: 0 0 8px;
        text-align: center;
        font-family: 'Roboto', sans-serif;
        font-size: 21px;
        color: #6c6464;
    }

    .types {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .chip {
        font-size: 12px;
        padding: 4px 8px;
        font-weight: 600;
        border: 1px solid #eee;
        border-radius: 999px;
        background: #fafafa;
        text-transform: capitalize;
    }

    .stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .stat {
        padding: 10px;
        border: 1px solid #f0f0f0;
        border-radius: 12px;
    }

    .label {
        font-size: 12px;
        color: #777;
    }

    .value {
        font-weight: 600;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }

    .modal {
        width: 100%;
        max-width: 520px;
        background: white;
        border-radius: 16px;
        overflow: hidden;
    }

    .modal-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 16px;
        border-bottom: 1px solid #eee;
    }

    h2 {
        margin: 0;
        font-family: 'Roboto', sans-serif;
    }

    .modal-body {
        padding: 16px;
        display: grid;
        gap: 12px;
    }

    .img-lg {
        max-height: 200px;
        margin: 0 auto;
    }

    .row {
        display: grid;
        gap: 8px;
    }
</style>