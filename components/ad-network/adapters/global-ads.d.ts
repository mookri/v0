/**
 * Global type definitions for Ad Engine bidders and third-party SDKs.
 */

interface Window {
  googletag?: any;
  FB?: any;
  apstag?: any;
  pbjs?: any;
  analytics?: any;
  mParticle?: any;
  rudderanalytics?: any;
  Shopify?: any;
  wp?: any;
  gtag?: any;
  ReactNativeWebView?: {
    postMessage: (msg: string) => void;
  };
}

declare var googletag: any;
declare var FB: any;
declare var apstag: any;
declare var pbjs: any;
declare var analytics: any;
declare var mParticle: any;
declare var rudderanalytics: any;
declare var Shopify: any;
declare var wp: any;
declare var gtag: any;
