 document.addEventListener("DOMContentLoaded", function() {
            const favoriteButtons = document.querySelectorAll(".favorite-btn");
            favoriteButtons.forEach(button => {
                button.addEventListener("click", function() {
                    // Toggle the heart icon color (red when active)
                    this.classList.toggle("active");

                    const hotelId = this.getAttribute("data-hotel-id");

                    // Send AJAX request to server to add/remove favorite
                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", "add_favorite.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            if (xhr.responseText === "added") {
                                console.log("Hotel added to favorites");
                            } else if (xhr.responseText === "removed") {
                                console.log("Hotel removed from favorites");
                            } else if (xhr.responseText === "not_logged_in") {
                                // Redirect to login page if user is not logged in
                                alert("You must be logged in to add a hotel to your favorites.");
                                window.location.href = "user_signin.html"; // Redirect to login page
                            }
                        }
                    };
                    xhr.send("hotel_id=" + hotelId);
                });
            });

            
        });