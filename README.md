# Guia de Bairro

**Guia de Bairro** é uma plataforma web desenvolvida em PHP para conectar moradores, visitantes e comerciantes a serviços, estabelecimentos e pontos de interesse de uma região.

---

## Funcionalidades

- **Busca e listagem de estabelecimentos:** Consulte restaurantes, lojas, farmácias e outros pontos de interesse por nome, categoria ou localização.
- **Cadastro e edição de locais:** Usuários autorizados podem cadastrar e editar estabelecimentos, incluindo endereço, horários e informações de contato.
- **Categorias e filtros:** Navegação facilitada por categorias e filtros personalizados.
- **Página detalhada do estabelecimento:** Cada local possui página própria com endereço, mapa, telefone, fotos e avaliações.
- **Painel administrativo:** Gerenciamento completo de cadastros, aprovações e categorias por meio de um painel web.
- **Sistema de avaliações:** Usuários podem avaliar estabelecimentos, e as avaliações passam por moderação.
- **Design responsivo:** Interface adaptada para dispositivos móveis e computadores.

---

## Implantação em Produção

### Pré-requisitos

- **Hospedagem com suporte a PHP 7.4+** (ex: HostGator, UOLHost, KingHost, DigitalOcean, VPS, etc)
- **Banco de dados MySQL ou MariaDB**
- **Acesso FTP, SSH ou painel de controle para upload dos arquivos**
- **Composer** (caso utilize dependências PHP externas)

### Passos de Implantação

1. **Faça upload dos arquivos do repositório para o diretório público da sua hospedagem**  
   (normalmente chamado de `public_html`, `www` ou similar).

2. **Instale as dependências** (se houver, via SSH):
    ```bash
    composer install
    ```

3. **Configure o banco de dados:**
    - Crie um banco de dados MySQL/MariaDB pelo painel da hospedagem.
    - Importe o script de estrutura disponível em `/database/` via phpMyAdmin ou linha de comando.
    - Configure as credenciais de conexão no arquivo `config/config.php` ou `.env`.

4. **Aponte o domínio ou subdomínio para a pasta `public/` do projeto.**
    - Certifique-se de definir o diretório raiz do site para a pasta `public/`.
    - Configure o `.htaccess` para redirecionamento de URLs amigáveis, se necessário.

5. **Acesse o sistema pelo navegador usando seu domínio:**
    ```
    https://www.seusite.com.br
    ```

---

## Exemplos de Uso Real

- **Moradores podem buscar restaurantes abertos agora e pedir delivery pelo telefone do estabelecimento.**
- **Comerciantes podem cadastrar sua loja e atualizar horários especiais (ex: feriados).**
- **Visitantes podem explorar os principais pontos turísticos do bairro com mapa e avaliações de outros usuários.**
- **Administradores podem aprovar novos cadastros e gerenciar categorias tudo via web.**

---

## Estrutura de Diretórios

- **public/** — Arquivos públicos (entrada do site, assets, imagens, CSS, JS)
- **src/** — Código da aplicação (controllers, models, helpers)
- **views/** — Templates HTML/PHP das páginas
- **config/** — Arquivos de configuração do sistema e do banco de dados
- **database/** — Scripts SQL para criação e seed do banco de dados

---

## Tecnologias Utilizadas

- **PHP 7.4+**
- **MySQL/MariaDB**
- **HTML5, CSS3 (responsivo, mobile-first)**
- **Composer** (opcional, para dependências)
- **MVC Simplificado**

---

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
