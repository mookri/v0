import { Service, Festival } from "@/types/quickActions";

export const services: Service[] = [
    {
        title: "Griha Shanti Puja",
        subtitle: "For peace and harmony in your new home",
        image: "/images/pujas/griha-shanti.jpg",
        href: "/pujas/griha-shanti",
        price: "2,199",
        duration: "60 mins",
        badge: "Trending",
    },
    {
        title: "Lakshmi Puja",
        subtitle: "Attract wealth and prosperity",
        image: "/images/pujas/lakshmi.jpg",
        href: "/pujas/lakshmi",
        price: "1,499",
        duration: "45 mins",
    },
    {
        title: "Ganesh Chaturthi Puja",
        subtitle: "Invoke Lord Ganesha's blessings",
        image: "/images/pujas/ganesh.jpg",
        href: "/pujas/ganesh",
        price: "3,199",
        duration: "90 mins",
        badge: "New",
    },
    {
        title: "Satyanarayan Puja",
        subtitle: "For family wellbeing and success",
        image: "/images/pujas/satyanarayan.jpg",
        href: "/pujas/satyanarayan",
        price: "2,499",
        duration: "60 mins",
    },
];

export const festival: Festival = {
    title: "Ganesh Chaturthi",
    status: "Tomorrow",
    image: "/images/festivals/ganesh-chaturthi.jpg",
    date: "23 August 2025",
    bookings: "12K+ Bookings",
    href: "/festivals/ganesh-chaturthi",
};