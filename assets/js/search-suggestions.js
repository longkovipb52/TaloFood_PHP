document.addEventListener('DOMContentLoaded', function() {
    const searchBox = document.getElementById('search-box');
    const suggestionsContainer = document.getElementById('search-suggestions');
    let typingTimer;
    const doneTypingInterval = 300; // ms
    
    // Bắt sự kiện khi người dùng gõ
    searchBox.addEventListener('input', function() {
        clearTimeout(typingTimer);
        
        const searchTerm = this.value.trim();
        if (searchTerm.length < 2) {
            suggestionsContainer.style.display = 'none';
            return;
        }
        
        typingTimer = setTimeout(() => {
            fetchSuggestions(searchTerm);
        }, doneTypingInterval);
    });
    
    // Bắt sự kiện click bên ngoài để ẩn gợi ý
    document.addEventListener('click', function(e) {
        if (!searchBox.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
    
    // Hàm lấy gợi ý từ server
    function fetchSuggestions(term) {
        fetch(`get_suggestions.php?term=${encodeURIComponent(term)}`)
            .then(response => response.json())
            .then(data => {
                displaySuggestions(data);
            })
            .catch(error => {
                console.error('Error fetching suggestions:', error);
            });
    }
    
    // Hiển thị gợi ý
    function displaySuggestions(data) {
        suggestionsContainer.innerHTML = '';
        
        // Hiển thị gợi ý dựa trên từ khóa
        if (data.suggestions && data.suggestions.length > 0) {
            const suggestionHeader = document.createElement('h4');
            suggestionHeader.textContent = 'Gợi ý tìm kiếm';
            suggestionsContainer.appendChild(suggestionHeader);
            
            data.suggestions.forEach(item => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `${item.food_name} <span class="suggestion-category">${item.foodcategory_name}</span>`;
                div.addEventListener('click', () => {
                    searchBox.value = item.food_name;
                    searchBox.form.submit();
                });
                suggestionsContainer.appendChild(div);
            });
        }
        
        // Hiển thị món ăn phổ biến
        if (data.popularItems && data.popularItems.length > 0) {
            const popularHeader = document.createElement('h4');
            popularHeader.textContent = 'Món ăn phổ biến';
            suggestionsContainer.appendChild(popularHeader);
            
            data.popularItems.forEach(item => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `${item.food_name} <span class="suggestion-category">${item.foodcategory_name}</span>`;
                div.addEventListener('click', () => {
                    searchBox.value = item.food_name;
                    searchBox.form.submit();
                });
                suggestionsContainer.appendChild(div);
            });
        }
        
        // Hiển thị container nếu có dữ liệu
        if (suggestionsContainer.children.length > 0) {
            suggestionsContainer.style.display = 'block';
        } else {
            suggestionsContainer.style.display = 'none';
        }
    }
});