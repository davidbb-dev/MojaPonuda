const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const typeFilter = document.getElementById('typeFilter');

const listings = document.querySelectorAll('.listing_container');

function filterListings(){
    const search = searchInput.value.toLowerCase();
    const status = statusFilter.value;
    const type = typeFilter.value;

    listings.forEach(el => {
        const name = el.dataset.name.toLowerCase();
        const elStatus = el.dataset.status;
        const elType = el.dataset.type;

        let show = true;

        if(search && !name.includes(search)) show = false;
        if(status !== 'all' && elStatus !== status) show = false;
        if(type !== 'all' && elType !== type) show = false;

        el.style.display = show ? 'flex' : 'none';
    });
}

searchInput.addEventListener('input', filterListings);
statusFilter.addEventListener('change', filterListings);
typeFilter.addEventListener('change', filterListings);