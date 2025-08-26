document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const body = document.body;
            const overlay = document.querySelector('.sidebar-overlay');
            // Sample rides data
            const ridesData = [
                { 
                    id: 1, 
                    name: "Lionel Messi",
                    title: "Gulshan to Airport", 
                    from: "Gulshan", 
                    to: "Shahjalal Int. Airport", 
                    price: "৳1500", 
                    time: "2:30 PM",
                },
                { 
                    id: 2, 
                    name: "Cristiano Ronaldo",
                    title: "Banani to Hatirjheel Trip", 
                    from: "Banani", 
                    to: "Hatirjheel", 
                    price: "৳550", 
                    time: "10:00 AM",
                },
                { 
                    id: 3, 
                    name: "Neymar Jr.",
                    title: "Dhanmondi to Uttara Route", 
                    from: "Dhanmondi", 
                    to: "Uttara", 
                    price: "৳700", 
                    time: "12:00 PM",
                },
                { 
                    id: 4, 
                    name: "Kylian Mbappé",
                    title: "University Shuttle", 
                    from: "Farmgate", 
                    to: "BUET Campus", 
                    price: "৳300", 
                    time: "2:30 PM",
                },
                { 
                    id: 5, 
                    name: "Luka Modrić",
                    title: "Shopping Run", 
                    from: "Mirpur", 
                    to: "Bashundhara City Mall", 
                    price: "৳450", 
                    time: "2:00 PM",
                },
                { 
                    id: 6, 
                    name: "Zlatan Ibrahimović",
                    title: "Nightlife Tour", 
                    from: "Gulshan", 
                    to: "Banani Entertainment Zone", 
                    price: "৳600", 
                    time: "6:00 PM",
                }
                    ];
            
            // Display user data
            // Function to display rides
            function displayRides(rides) {
                const container = document.getElementById('ridesContainer');
                container.innerHTML = ''; // Clear existing content
                
                rides.forEach(ride => {
                    const rideCard = document.createElement('div');
                    rideCard.className = 'ride-card';
                    rideCard.innerHTML = `
                        <div class="ride-image">
                            <div class="card-image">
                                <img src="../../Asset/icons/person.png" alt="User Image">
                            </div>
                            <h3 class="rider-name">${ride.name}</h3>
                        </div>
                    
                        <div class="ride-details">
                            <h3 class="ride-title">${ride.title}</h3>
                            <div class="ride-info">
                                <span>${ride.from} → ${ride.to}</span>
                                <span>${ride.time}</span>
                            </div>
                            <div class="ride-price">${ride.price}</div>
                            <button class="ride-action">Book Now</button>
                        </div>
                    `;
                    container.appendChild(rideCard);
                });
            }
            
            // Initial display of rides
            displayRides(ridesData);
            
            // Search functionality
            const searchInput = document.getElementById('search-input');
            const searchBtn = document.getElementById('search-btn');
            
            searchBtn.addEventListener('click', performSearch);
            searchInput.addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    performSearch();
                }
            });
            
            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase();
                if (searchTerm.trim() === '') {
                    displayRides(ridesData);
                    return;
                }
                
                const filteredRides = ridesData.filter(ride => 
                    ride.title.toLowerCase().includes(searchTerm) ||
                    ride.from.toLowerCase().includes(searchTerm) ||
                    ride.to.toLowerCase().includes(searchTerm)
                );
                
                displayRides(filteredRides);
            }
        });