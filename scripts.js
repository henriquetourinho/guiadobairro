// Aguarda o conteúdo da página ser totalmente carregado
document.addEventListener('DOMContentLoaded', () => {

    // --- Lógica para o Tema (Theme Switcher) ---
    const themeToggleButton = document.getElementById('theme-toggle');

    const setTheme = (theme) => {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
    };

    if (themeToggleButton) { // Verifica se o botão existe antes de adicionar o listener
        themeToggleButton.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    }

    // Garante que o estado inicial do botão corresponda ao tema carregado
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }


    // --- Lógica para Formatação de CNPJ no Cadastro ---
    const cnpjCpfInput = document.getElementById('cnpj_cpf');

    if (cnpjCpfInput) { // Verifica se o campo CNPJ/CPF existe na página
        cnpjCpfInput.addEventListener('input', (e) => {
            let value = e.target.value;

            // Remove todos os caracteres que não sejam dígitos
            value = value.replace(/\D/g, '');

            // Limita o tamanho máximo a 14 dígitos limpos (para CNPJ)
            if (value.length > 14) {
                value = value.substring(0, 14);
            }

            // Formatação para CNPJ (XX.XXX.XXX/XXXX-XX)
            // Adapte estas regex para o formato exato que você deseja
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');

            e.target.value = value;
        });

        // Opcional: Para preencher com o valor salvo do PHP (se houver erro e o campo for preenchido de volta)
        // Dispara o evento input uma vez ao carregar a página para formatar o valor inicial
        if (cnpjCpfInput.value) {
            cnpjCpfInput.dispatchEvent(new Event('input'));
        }
    }


    // --- Lógica para Busca Dinâmica e Filtro de Lojas (AJAX) ---
    const searchInput = document.getElementById('search-input');
    const lojasResultsContainer = document.getElementById('lojas-results');
    const clearSearchBtn = document.getElementById('clear-search-btn');
    const categoriesFilterList = document.getElementById('categories-filter-list');

    let searchTimeout;
    let currentCategoryId = 'all'; // Armazena a categoria atualmente selecionada ('all' para todas)

    // Função para renderizar os cartões de loja
    const renderLojas = (lojas) => {
        lojasResultsContainer.innerHTML = ''; // Limpa os resultados anteriores

        if (lojas.length === 0) {
            lojasResultsContainer.innerHTML = `
                <div class="aviso-geral" style="grid-column: 1 / -1;">
                    <p>Nenhum estabelecimento encontrado para sua pesquisa.</p>
                </div>
            `;
            return;
        }

        lojas.forEach(loja => {
            const cartao = document.createElement('article');
            cartao.classList.add('cartao');

            // Lógica para a imagem de capa
            let imgHtml = '';
            // Assume que se foto_capa tem um valor, o link é válido e o arquivo existe
            if (loja.foto_capa && !loja.foto_capa.includes('null') && loja.foto_capa !== '') { // Verifica se foto_capa não é nula/vazia
                // Adicione uma verificação para ver se o arquivo realmente existe no servidor se o caminho for relativo.
                // Para AJAX, é melhor ter o servidor retornando um link completo ou um fallback.
                // Por simplicidade, vamos usar o caminho retornado pelo PHP.
                imgHtml = `
                    <div class="cartao-img-container">
                        <img src="${loja.foto_capa}" alt="Foto de Capa da Loja ${loja.nome_loja}" class="cartao-img">
                    </div>
                `;
            } else {
                imgHtml = `
                    <div class="cartao-img-container placeholder-img">
                        <i class="fa-solid fa-store"></i>
                        <span>Sem foto de capa</span>
                    </div>
                `;
            }

            // Limita a descrição para a exibição no cartão (igual ao PHP)
            const truncatedDescription = loja.descricao.length > 110 ?
                loja.descricao.substring(0, 110) + '...' : loja.descricao;

            cartao.innerHTML = `
                ${imgHtml}
                <div class="cartao-body">
                    <span class="cartao-categoria">
                        <i class="fa-solid fa-tag"></i> ${loja.nome_categoria}
                    </span>
                    <h2 class="cartao-titulo">${loja.nome_loja}</h2>
                    <p class="cartao-descricao">${truncatedDescription}</p>
                </div>
                <div class="cartao-footer">
                    <a href="loja.php?id=${loja.id}" class="btn-detalhes">
                        Ver Mais Detalhes <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            `;
            lojasResultsContainer.appendChild(cartao);
        });
    };

    // Função para buscar lojas via AJAX (agora com filtro de categoria)
    const fetchLojas = async (query = '', categoryId = 'all') => {
        let url = `buscar_lojas.php?q=${encodeURIComponent(query)}`;
        if (categoryId !== 'all') {
            url += `&category_id=${encodeURIComponent(categoryId)}`;
        }

        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            if (data.error) {
                console.error("Erro do servidor:", data.error);
                lojasResultsContainer.innerHTML = `
                    <div class="aviso-geral" style="grid-column: 1 / -1;">
                        <p>Ocorreu um erro ao buscar estabelecimentos. Tente novamente mais tarde.</p>
                    </div>
                `;
            } else {
                renderLojas(data);
            }
        } catch (error) {
            console.error("Erro na requisição Fetch:", error);
            lojasResultsContainer.innerHTML = `
                <div class="aviso-geral" style="grid-column: 1 / -1;">
                    <p>Não foi possível conectar ao servidor para buscar estabelecimentos. Verifique sua conexão.</p>
                </div>
            `;
        }
    };

    // Função para buscar e renderizar os botões de categoria
    const fetchAndRenderCategories = async (currentSearchQuery = '') => {
        const url = `buscar_categorias_contagem.php?q=${encodeURIComponent(currentSearchQuery)}`;
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.error) {
                console.error("Erro ao buscar contagem de categorias:", data.error);
                return;
            }

            categoriesFilterList.innerHTML = ''; // Limpa os botões existentes

            // Adiciona o botão "Todas"
            const allBtn = document.createElement('button');
            allBtn.classList.add('category-btn');
            if (currentCategoryId === 'all') {
                allBtn.classList.add('active');
            }
            allBtn.setAttribute('data-category-id', 'all');
            allBtn.innerHTML = `Todas <span class="category-count">(${data.total_lojas})</span>`;
            categoriesFilterList.appendChild(allBtn);

            // Adiciona os botões para cada categoria
            data.categories.forEach(category => {
                if (category.loja_count > 0) { // Mostra apenas categorias com lojas
                    const categoryBtn = document.createElement('button');
                    categoryBtn.classList.add('category-btn');
                    if (currentCategoryId === category.id) {
                        categoryBtn.classList.add('active');
                    }
                    categoryBtn.setAttribute('data-category-id', category.id);
                    categoryBtn.innerHTML = `${category.nome_categoria} <span class="category-count">(${category.loja_count})</span>`;
                    categoriesFilterList.appendChild(categoryBtn);
                }
            });

            // Adiciona event listeners aos novos botões de categoria
            document.querySelectorAll('.category-btn').forEach(button => {
                button.addEventListener('click', () => {
                    // Remove 'active' de todos os botões e adiciona ao clicado
                    document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    
                    currentCategoryId = button.getAttribute('data-category-id');
                    fetchLojas(searchInput.value, currentCategoryId); // Re-busca lojas com a nova categoria e termo de pesquisa atual
                });
            });

        } catch (error) {
            console.error("Erro na requisição Fetch para categorias:", error);
        }
    };


    // Event Listener para o campo de pesquisa
    if (searchInput) { // Garante que a lógica de busca só rode se o campo existe (apenas no index.php)
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout); // Limpa o timeout anterior
            const query = searchInput.value;

            if (query.length > 0) {
                clearSearchBtn.style.display = 'inline-block';
            } else {
                clearSearchBtn.style.display = 'none';
            }

            searchTimeout = setTimeout(() => {
                fetchLojas(query, currentCategoryId); // Filtra lojas por termo e categoria atual
                fetchAndRenderCategories(query); // Atualiza contagem de categorias com base no termo
            }, 300);
        });

        // Event listener para o botão de limpar pesquisa
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = ''; // Limpa o input
                clearSearchBtn.style.display = 'none'; // Esconde o botão
                // Seleciona o botão 'Todas' categorias e busca tudo
                document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
                const allCategoriesBtn = document.querySelector('.category-btn[data-category-id="all"]');
                if (allCategoriesBtn) {
                    allCategoriesBtn.classList.add('active');
                    currentCategoryId = 'all';
                }
                fetchLojas('', 'all'); // Limpa tudo e busca
                fetchAndRenderCategories(''); // Re-carrega contagem de categorias sem filtro de busca
                searchInput.focus();
            });
        }
        
        // Carga inicial das categorias e lojas
        fetchAndRenderCategories(searchInput.value); // Carrega categorias com contagem (respeitando o termo se já houver)
        fetchLojas(searchInput.value, currentCategoryId); // Carrega lojas iniciais (respeitando o termo e categoria inicial)
    }
});