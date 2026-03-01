# 🧩 PokeAgenda

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)]()
[![Vue](https://img.shields.io/badge/Vue-3.x-42b883.svg)]()
[![PHP](https://img.shields.io/badge/PHP-8.3-blue.svg)]()
[![Tests](https://img.shields.io/badge/Tests-Passing-brightgreen.svg)]()
[![License](https://img.shields.io/badge/license-MIT-blue.svg)]()

Aplicação fullstack desenvolvida com **Laravel 12 + Vue 3**, responsável por importar dados da PokeAPI, persistir em banco relacional e disponibilizar interface moderna com paginação, filtros e testes automatizados.

---

## 🚀 Tecnologias Utilizadas

### Backend
- PHP 8.3
- Laravel 12
- MySQL
- Eloquent ORM
- HTTP Client
- PHPUnit
- SQLite (ambiente de teste)
- Docker

### Frontend
- Vue.js 3 (Composition API)
- Vite
- Axios
- @vueform/multiselect
- lucide-vue-next
- CSS customizado

---

## 🏗 Arquitetura

Separação clara de responsabilidades:

- **Controllers**
  - `PokemonController` → listagem e paginação
  - `PokemonImportController` → execução manual da importação

- **Service**
  - `FeedsServices` → integração com a PokeAPI

- **Models**
  - `FeedsModel` → tabela `pokemons`
  - `TypeModel` → tabela `types`

Relacionamento:

---

## 📦 Estrutura de Banco

### `pokemons`
| Campo | Tipo |
|-------|------|
| id | PK |
| poke_id | int (unique) |
| name | string |
| api_url | string |
| height | int |
| weight | int |
| dream_world_svg | string |

### `types`
| Campo | Tipo |
|-------|------|
| id | PK |
| pokemon_id | FK |
| name | string |

---

## 🐳 Instalação com Docker

git clone <repo-url><br/>
cd Logcomex<br/>
<br/>
docker compose up -d --build<br/>
docker exec -it pokeagenda-app bash<br/>
composer install<br/>
npm install *Fora do container*<br/>
cp .env.example .env<br/>
php artisan key:generate<br/>
php artisan migrate<br/>
npm run dev<br/>
php artisan test<br/>
php artisan test --filter PokemonApiTest<br/>
php artisan test --filter FeedsServicesTest<br/>