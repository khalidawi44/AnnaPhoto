<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="abp-block-tab">
</div>
<div class="row">
    <div class="col-lg-3">
        <div class="search-wrapper-outer">
            <h1>Theme Categories</h1>
            <!-- Dropdown-->
            <div class="abp-navbar-wrapper">
                <!-- New Tabs Section -->
                <div id="category-tabs" class="tabs-wrapper">
                    <!-- Tabs -->
                </div>

            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="row header-main-block" style="justify-content: space-between;">
            <div class="col-lg-5 search-logo-box">
                <h1>Professional WordPress Themes</h1>
            </div>
            <div class="col-lg-7 search-container">
                <input type="text" id="search-box" class="form-control" placeholder="Search products...">

            </div>
        </div>

        <div class="banner-section">
            <a class="banner-url-btn" href="<?php echo esc_url(ABP_MAIN_URL . 'products/wordpress-theme-bundle'); ?>"
                target="_blank" rel="noopener noreferrer">
                <img src="<?php echo esc_url(plugins_url('images/banner-img2.png', __FILE__)); ?>" alt="banner-img">
            </a>
        </div>

        <div id="product-cards" class="row mt-3"></div>
        <div id="abp-scroll-sentinel"></div>
        <div id="loader" style="display:none; text-align:center;">
            <div class="loader-img-wrap">
                <img src="<?php echo esc_url(plugins_url('images/loader3.gif', __FILE__)); ?>" alt="Loading...">
            </div>
            <button id="load-more" class="btn btn-primary" style="display:none;">Load More</button>
        </div>
    </div>
</div>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        const apiEndpoint = '<?php echo esc_url(plugin_dir_url(__FILE__) . 'abp-get-api.php'); ?>';
        let endCursor = null;
        let searchQuery = '';
        let selectedCollection = '';
        let debounceTimeout = null;
        let activeTab = null;
        let isFetching = false;
        let observer = null;
        function fetchCollections() {
            return fetch(apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'getCollections' })
            })
                .then(response => response.json())
                .then(data => {
                    populateTabs(data);
                })
                .catch(error => console.error('Error fetching collections:', error));
        }


        // Populate Tabs
        function populateTabs(data) {
            const tabsContainer = document.getElementById('category-tabs');
            tabsContainer.innerHTML = '';

            if (data && data.data && Array.isArray(data.data)) {
                const allThemesIndex = data.data.findIndex(collection => collection.title === 'All Themes');
                if (allThemesIndex !== -1) {
                    const allThemes = data.data.splice(allThemesIndex, 1)[0];
                    data.data.unshift(allThemes);
                }

                data.data.slice(0, -1).filter(collection => collection.handle !== 'free-wordpress-themes').forEach((collection, index) => {
                    const tab = document.createElement('button');
                    tab.classList.add('btn', 'tab-btn');
                    tab.textContent = collection.title == 'Th-premium' ? 'All Themes' : collection.title;
                    tab.setAttribute('data-value', collection.handle);

                    tab.addEventListener('click', function () {
                        const clickedTab = this;
                        if (!clickedTab.classList.contains('active')) {
                            if (activeTab) activeTab.classList.remove('active');

                            clickedTab.classList.add('active');
                            activeTab = clickedTab;
                            selectedCollection = clickedTab.getAttribute('data-value');
                            fetchApiData();
                        }
                    });

                    tabsContainer.appendChild(tab);

                    if (index < 9) {
                        const separator = document.createElement('span');
                    }
                });
            }
        }


        function fetchApiData(afterCursor = null, append = false, showLoader = true) {
            if (isFetching) return;
            isFetching = true;

            if (showLoader && !append) {
                document.getElementById('loader').style.display = 'block'; // Show loader
            }

            return fetch(apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'getProducts',
                    collectionHandle: selectedCollection,
                    productHandle: searchQuery,
                    paginationParams: { first: 6, afterCursor: afterCursor, reverse: true }
                })
            })
                .then(response => response.json())
                .then(data => {
                    displayData(data, append);
                    endCursor = data.data.pageInfo.endCursor;

                    // Check if more pages are available
                    if (data.data.pageInfo.hasNextPage) {
                        setupObserver();
                    } else {
                        if (observer) { observer.disconnect(); observer = null; }
                    }

                    isFetching = false;
                    document.getElementById('loader').style.display = 'none'; // Hide loader
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                    isFetching = false;
                });
        }

        // Display Fetched Products
        function displayData(data, append = false) {
            const productCards = document.getElementById('product-cards');
            if (!append) productCards.innerHTML = '';

            if (data && data.data && Array.isArray(data.data.products)) {
                let filteredProducts = data.data.products;

                if (searchQuery) {
                    filteredProducts = filteredProducts.filter(product => product.node.inCollection === true);
                }

                filteredProducts.forEach(product => {
                    const item = product.node;
                    const imageSrc = item.images.edges[0]?.node.src || 'default-image.jpg';
                    const price = item.variants.edges[0]?.node.price || 'N/A';
                    const compareprice = item.variants.edges[0]?.node.compareAtPrice || 'N/A';
                    const demoLink = item.metafield?.value || '#';

                    const colElement = document.createElement('div');
                    colElement.classList.add('col-12', 'col-md-6', 'col-lg-4', 'mb-3');
                    colElement.innerHTML = `
                        <div class="card">
                            <div class="card-img-wrap">
                                <img src="${imageSrc}" class="card-img-top" alt="${item.title}">

                            </div>
                            <div class="card-body">
                                <h5 class="card-title">${item.title}</h5>
                                <p class="card-text"><span>Price: </span>$${price}
                                <del class="abp-compareprice" >$${compareprice}</del>
                                </p>
                                
                                <div class="abp-button-wrapper">
                                    <a href="${item.onlineStoreUrl}" class="btn btn-primary buy-now" target="_blank">Buy Now</a>
                                    <a href="${demoLink}" class="btn btn-primary demo" target="_blank" style="margin-left: 10px;">Demo</a>
                                </div>
                            </div>
                        </div>
                    `;
                    productCards.appendChild(colElement);
                });
            } else {
                console.error('Data format is incorrect:', data);
            }
        }

        function setupObserver() {
            if (observer) observer.disconnect();
            const sentinel = document.getElementById('abp-scroll-sentinel');
            if (!sentinel) return;
            observer = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting && endCursor && !isFetching) {
                    fetchApiData(endCursor, true);
                }
            }, { rootMargin: '600px' });
            observer.observe(sentinel);
        }

        function debouncedSearch() {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(function () {
                endCursor = null;
                fetchApiData();
            }, 1000);
        }

        // Event listener for search input
        document.getElementById('search-box').addEventListener('input', function () {
            searchQuery = this.value.trim();
            debouncedSearch();
        });


        fetchCollections(); // Fetch collections on page load
        fetchApiData(); // Fetch products initially
    });
</script>