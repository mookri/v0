import { Festival, Service } from "@/types/quickActions";

export const services: Service[] = [
    {
        title: "Book a Puja",
        subtitle: "Temple • Home",
        href: "/book",
        imgQuery: "hindu puja rituals",
        gradient: "from-orange-50 to-amber-100",
    },
    {
        title: "Find a Pandit",
        subtitle: "Verified Experts",
        href: "/pandits",
        imgQuery: "hindu pandit priest",
        gradient: "from-emerald-50 to-green-100",
    },
    {
        title: "Festivals",
        subtitle: "Upcoming Events",
        href: "/festivals",
        imgQuery: "hindu festival diya",
        gradient: "from-rose-50 to-pink-100",
    },
    {
        title: "Astrology",
        subtitle: "Horoscope",
        href: "/astrology",
        imgQuery: "vedic astrology",
        gradient: "from-indigo-50 to-violet-100",
    },
    {
        title: "Vastu",
        subtitle: "Home Energy",
        href: "/vastu",
        imgQuery: "vastu shastra",
        gradient: "from-teal-50 to-cyan-100",
    },
    {
        title: "E-Puja",
        subtitle: "Online Rituals",
        href: "/e-puja",
        imgQuery: "online puja",
        gradient: "from-sky-50 to-blue-100",
    },
    {
        title: "Matchmaking",
        subtitle: "Kundli Match",
        href: "/astrology/kundli",
        imgQuery: "indian wedding couple",
        gradient: "from-pink-50 to-rose-100",
    },
    {
        title: "Samagri",
        subtitle: "Shop Items",
        href: "/store",
        imgQuery: "puja samagri",
        gradient: "from-yellow-50 to-orange-100",
    },
];

export const festival: Festival = {
    title: "Ganesh Chaturthi",
    date: "23 August 2025",
    status: "Starts Tomorrow",
    bookings: "12K+ Bookings",
    image:
        "/.jpg?key=s7bpr&height=500&width=400&query=golden ganesha statue white background",
    href: "/pujas",
};