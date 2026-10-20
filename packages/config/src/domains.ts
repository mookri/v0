export const domains = {
  development: {
    web: "http://navdhya.localhost",
    customer: "http://app.navdhya.localhost",
    api: "http://api.navdhya.localhost",
  },
  staging: {
    web: "https://staging.navdhya.com",
    customer: "https://app.staging.navdhya.com",
    api: "https://api.staging.navdhya.com",
  },
  production: {
    web: "https://navdhya.com",
    customer: "https://app.navdhya.com",
    api: "https://api.navdhya.com",
  },
} as const;
