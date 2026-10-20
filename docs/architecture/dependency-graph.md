# Architecture Dependency Graph

## Business Capabilities

The capabilities follow a strict dependency direction. Cycles (e.g., A -> B -> A) are forbidden.

```mermaid
graph TD
    User --> Foundation
    Puja --> Foundation
    
    Pandit --> User
    Pandit --> Foundation
    
    Availability --> Puja
    Availability --> Pandit
    Availability --> Foundation
    
    Pricing --> Puja
    Pricing --> Foundation
    
    Booking --> User
    Booking --> Puja
    Booking --> Pandit
    Booking --> Availability
    Booking --> Pricing
    Booking --> Foundation
    
    Search --> Puja
    Search --> Pandit
    Search --> Content
    Search --> Foundation
    
    Recommendations --> User
    Recommendations --> Puja
    Recommendations --> Pandit
    Recommendations --> Booking
    Recommendations --> Foundation
```

## Application vs Capability

```mermaid
graph TD
    Web[navdhya.com] --> Search
    Web --> Recommendations
    Web --> Booking
    Web --> Puja
    Web --> Pandit
    
    CustomerApp[app.navdhya.com] --> Booking
    CustomerApp --> User
    CustomerApp --> Recommendations
    
    PartnerApp[partner.navdhya.com] --> Pandit
    PartnerApp --> Availability
    PartnerApp --> Booking
```

## Capability Internal Architecture (Ports & Adapters)

Each capability strictly implements a Ports & Adapters (Hexagonal) architecture.

```mermaid
graph TD
    subgraph UI / App layer
      Nextjs[Next.js App Router]
    end
    
    subgraph Capability [Booking Capability]
      App[Application / Use Cases]
      Domain[Domain Models & Rules]
      Ports[Ports / Interfaces]
      Contracts[Public Contracts]
    end
    
    subgraph Infrastructure
      DBAdapter[Postgres Adapter]
      PaymentAdapter[Payment Gateway]
    end
    
    Nextjs -->|Imports| Contracts
    Nextjs -->|Executes| App
    App --> Domain
    App --> Ports
    DBAdapter -->|Implements| Ports
    PaymentAdapter -->|Implements| Ports
```
