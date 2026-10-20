# Customer feature: home

Route: `customer.home` (`/`)
Orchestration: `application/home/get-customer-home.ts` over User + Booking + Recommendations.
View model: `CustomerHomeViewModel{greeting,upcomingBooking?,quickActions,recommendations,recentActivity}`.
Analytics: `customer.home.viewed`.
Migration state: IN_PROGRESS.
