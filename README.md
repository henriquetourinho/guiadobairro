# Guia de Bairro — Guia Local de Serviços e Estabelecimentos

<p align="left">
  <img src="https://img.shields.io/badge/versão-v1.0-blue.svg" alt="Versão" />
  <img src="https://img.shields.io/badge/licença-GPL--3.0-blue.svg" alt="Licença" />
  <img src="https://img.shields.io/badge/PHP-7.4%2B-cyan.svg" alt="PHP Version" />
  <img src="https://img.shields.io/badge/plataformas-Linux | macOS | Windows-blue.svg" alt="Plataformas Suportadas" />
</p>

## 1. Introdução

**guiadobairro** é uma plataforma web desenvolvida em PHP para conectar moradores e visitantes a serviços, estabelecimentos e pontos de interesse do bairro. O sistema foi projetado para ser simples, leve e adaptável, facilitando a busca por categorias, localização e destaque de comércios locais.

## 2. Funcionalidades Principais

- **Listagem e busca de estabelecimentos:** Consulte restaurantes, lojas, farmácias e outros pontos de interesse por nome, categoria ou localização.
- **Cadastro e edição de locais:** Usuários autorizados podem cadastrar novos pontos, editar descrições, horários e informações de contato.
- **Categorias e filtros:** Navegação facilitada por categorias e filtros rápidos.
- **Detalhes completos:** Cada local possui página própria com mapa, horários, endereço, telefone e fotos.
- **Painel administrativo:** Gerenciamento simples de cadastros, aprovações e categorias.
- **Responsividade:** Interface adaptada para uso em dispositivos móveis e computadores.

## 3. Instalação e Execução

### 3.1. Pré-requisitos

- PHP 7.4 ou superior
- Servidor web Apache/Nginx
- MySQL/MariaDB (opcional, dependendo da configuração)
- Composer (para dependências, se aplicável)

### 3.2. Instalação

Clone o repositório:

```bash
git clone https://github.com/henriquetourinho/guiadobairro.git
cd guiadobairro
```

Instale as dependências (se houver):

```bash
composer install
```

Configure o banco de dados no arquivo `config.php` ou `.env` (detalhes no próprio arquivo).

### 3.3. Execução

- Suba o projeto em um servidor web local ou use o embutido do PHP:
```bash
php -S localhost:8080 -t public/
```
- Acesse via navegador: [http://localhost:8080](http://localhost:8080)

## 4. Estrutura de Diretórios

- `public/` – Arquivos públicos (index.php, assets)
- `src/` – Lógica de aplicação (controllers, models, helpers)
- `views/` – Templates e páginas HTML/PHP
- `config/` – Arquivos de configuração
- `database/` – Scripts de banco de dados e seeds
- `README.md` – Este arquivo

## 5. Exemplos de Uso

- Encontrar todos os restaurantes do bairro em segundos.
- Cadastrar seu próprio negócio local para aparecer nas buscas.
- Filtrar estabelecimentos abertos agora ou com delivery.
- Administrar facilmente as informações do seu comércio.

## 6. Limitações

- Projeto voltado para uso comunitário ou como base para customizações.
- Não indicado para grandes cidades sem customização de performance.
- Requer configuração manual de ambiente em hospedagens compartilhadas.

## 7. Apoie o Projeto

Se o **guiadobairro** for útil, considere apoiar o desenvolvimento ou contribuir.

**Chave Pix:**  
```
poupanca@henriquetourinho.com.br
```

---

## 📄 Licença

Distribuído sob a licença **GPL-3.0** — consulte o arquivo `LICENSE` para detalhes.

---

## 🙋‍♂️ Desenvolvido por

**Carlos Henrique Tourinho Santana** — Salvador, Bahia, Brasil  
🔗 [Wiki Debian](https://wiki.debian.org/henriquetourinho)  
🔗 [LinkedIn](https://br.linkedin.com/in/carloshenriquetourinhosantana)  
🔗 [GitHub](https://github.com/henriquetourinho)
