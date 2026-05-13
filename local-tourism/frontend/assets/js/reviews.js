const PROVINCES = ["luzon", "visayas", "mindanao"];

function setupMobileNav() {
    const navbar = document.querySelector(".navbar");
    const menuToggle = document.getElementById("menu-toggle");
    const navLinks = document.querySelectorAll(".nav-links a");

    if (!navbar || !menuToggle) return;

    menuToggle.addEventListener("click", () => {
        const isOpen = navbar.classList.toggle("nav-open");
        menuToggle.setAttribute("aria-expanded", String(isOpen));
    });

    navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            navbar.classList.remove("nav-open");
            menuToggle.setAttribute("aria-expanded", "false");
        });
    });
}

function pluralizeReviews(count) {
    return `${count} review${count === 1 ? "" : "s"}`;
}

function renderProvinceProgress(provinceProgress = []) {
    const progressByProvince = {};
    provinceProgress.forEach((item) => {
        const key = String(item.province || "").toLowerCase();
        progressByProvince[key] = item;
    });

    PROVINCES.forEach((province) => {
        const data = progressByProvince[province] || { respondents: 0, percentage: 0 };
        const bar = document.getElementById(province);
        const review = document.getElementById(`${province}-review`);

        if (bar) {
            const width = Math.max(0, Math.min(100, Number(data.percentage) || 0));
            bar.style.width = `${width}%`;
        }

        if (review) {
            review.textContent = pluralizeReviews(Number(data.respondents) || 0);
        }
    });
}

function renderTopDestinations(topDestinations = []) {
    const destinationList = document.getElementById("destination-list");
    if (!destinationList) return;

    destinationList.innerHTML = "";

    if (!Array.isArray(topDestinations) || topDestinations.length === 0) {
        const emptyPill = document.createElement("div");
        emptyPill.className = "destination";
        emptyPill.textContent = "No data yet";
        destinationList.appendChild(emptyPill);
        return;
    }

    topDestinations.forEach((destination) => {
        const destinationPill = document.createElement("div");
        destinationPill.className = "destination";
        destinationPill.textContent = destination.destination_name || "Unknown";
        destinationList.appendChild(destinationPill);
    });
}

async function fetchReviews() {
    try {
        const response = await fetch("../../backend/api/get-reviews.php");
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || "Unable to load review data");
        }

        renderProvinceProgress(data.province_progress || []);
        renderTopDestinations(data.top_destinations || []);
    } catch (error) {
        renderProvinceProgress([]);
        renderTopDestinations([]);
        console.error("Failed to load reviews:", error);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    setupMobileNav();
    fetchReviews();
});
