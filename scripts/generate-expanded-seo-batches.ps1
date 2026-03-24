param(
  [string[]]$SourcePaths = @(
    "content/sources/hindu-ceremony-pages-batch-2.tsv",
    "content/sources/puja-guide-pages.tsv",
    "content/sources/pandit-service-pages.tsv",
    "content/sources/astrology-consultation-pages.tsv",
    "content/sources/ritual-prep-pages.tsv",
    "content/sources/festival-ritual-pages.tsv",
    "content/sources/household-ritual-pages.tsv",
    "content/sources/spiritual-lifestyle-pages.tsv",
    "content/sources/regional-tradition-pages.tsv",
    "content/sources/platform-technology-pages.tsv"
  )
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$pagesDir = Join-Path $root "content/pages"
$lastUpdated = "2026-03-20T00:00:00.000Z"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Join-Words {
  param([string[]]$Values)

  $clean = @($Values | Where-Object { $_ -and $_.Trim() })
  if ($clean.Count -eq 0) { return "" }
  if ($clean.Count -eq 1) { return $clean[0] }
  if ($clean.Count -eq 2) { return "$($clean[0]) and $($clean[1])" }

  $lead = @($clean | Select-Object -First ($clean.Count - 1))
  return "$($lead -join ", "), and $($clean[-1])"
}

function Convert-ToPhraseList {
  param([string]$Value)

  return @($Value -split ";" | ForEach-Object { $_.Trim() } | Where-Object { $_ })
}

function Trim-ToLength {
  param(
    [string]$Value,
    [int]$MaxLength
  )

  if (-not $Value) { return "" }
  if ($Value.Length -le $MaxLength) { return $Value }

  $trimmed = $Value.Substring(0, $MaxLength)
  $trimmed = $trimmed -replace "\s+\S*$", ""
  return "$trimmed..."
}

function Get-AuthorForEntry {
  param($Entry)

  if ($Entry.contentFamily -like "astrology-*") {
    return "Navdhya Spiritual Guidance Team"
  }

  if ($Entry.contentFamily -like "ritual-prep-*") {
    return "Navdhya Ritual Preparation Team"
  }

  if ($Entry.contentFamily -like "regional-*") {
    return "Navdhya Regional Traditions Team"
  }

  if ($Entry.contentFamily -like "platform-tech*") {
    return "Navdhya Platform Team"
  }

  switch ($Entry.cluster) {
    "festival-rituals" { return "Navdhya Festival Guides Team" }
    "ritual-guides" { return "Navdhya Ritual Guides Team" }
    "spiritual-guides" { return "Navdhya Spiritual Guidance Team" }
    "services" { return "Navdhya Services Team" }
    "pandit-services" {
      switch ($Entry.schemaType) {
        "Service" { return "Navdhya Pandit Services Team" }
        "LocalBusiness" { return "Navdhya Pandit Services Team" }
        "HowTo" { return "Navdhya Pandit Services Team" }
        default { return "Navdhya Priest Network Team" }
      }
    }
    default { return "Navdhya Ceremonies Team" }
  }
}

function Get-PrimaryTopicText {
  param($Entry)

  return "$($Entry.title) $($Entry.primaryKeyword) $($Entry.summary)"
}

function Get-SecondaryPhrase {
  param($Entry)

  $selected = @($Entry.secondaryKeywords | Select-Object -First 3)
  return Join-Words $selected
}

function Get-SecondaryPair {
  param($Entry)

  $selected = @($Entry.secondaryKeywords | Select-Object -First 2)
  return Join-Words $selected
}

function New-Section {
  param(
    [string]$Heading,
    [string[]]$Paragraphs
  )

  return [ordered]@{
    heading = $Heading
    paragraphs = @($Paragraphs | Where-Object { $_ -and $_.Trim() })
  }
}

function Render-Sections {
  param([array]$Sections)

  $html = foreach ($section in $Sections) {
    $paragraphHtml = foreach ($paragraph in $section.paragraphs) {
      "<p>$paragraph</p>"
    }
    $joinedParagraphs = $paragraphHtml -join ""

    "<h2>$($section.heading)</h2>$joinedParagraphs"
  }

  return [ordered]@{
    content = ($html -join "")
    outline = @($Sections | ForEach-Object { $_.heading })
  }
}

function New-MetaTitle {
  param($Entry)
  return "$($Entry.title) | Navdhya"
}

function New-MetaDescription {
  param($Entry)

  $seed = "Learn about $($Entry.primaryKeyword) with Navdhya. $($Entry.summary)"
  return Trim-ToLength -Value $seed -MaxLength 158
}

function Get-ReadingTime {
  param(
    [string]$Content,
    [array]$Faqs,
    [array]$HowToSteps
  )

  $text = "$Content $((@($Faqs) | ForEach-Object { "$($_.question) $($_.answer)" }) -join " ") $((@($HowToSteps) | ForEach-Object { "$($_.name) $($_.text)" }) -join " ")"
  $plain = ($text -replace "<[^>]+>", " " -replace "\s+", " ").Trim()
  $words = @($plain -split "\s+" | Where-Object { $_ }).Count
  return [Math]::Max(6, [Math]::Ceiling($words / 190))
}

function Get-NeighborSlugs {
  param(
    [array]$Entries,
    [int]$Index,
    [string]$Property,
    [int]$Limit
  )

  $target = $Entries[$Index].$Property
  $found = New-Object System.Collections.Generic.List[string]

  for ($offset = 1; $offset -lt $Entries.Count -and $found.Count -lt $Limit; $offset++) {
    $prev = $Index - $offset
    if ($prev -ge 0 -and $Entries[$prev].$Property -eq $target -and $Entries[$prev].slug -ne $Entries[$Index].slug) {
      $found.Add($Entries[$prev].slug)
    }

    if ($found.Count -ge $Limit) { break }

    $next = $Index + $offset
    if ($next -lt $Entries.Count -and $Entries[$next].$Property -eq $target -and $Entries[$next].slug -ne $Entries[$Index].slug) {
      $found.Add($Entries[$next].slug)
    }
  }

  return @($found | Select-Object -Unique | Select-Object -First $Limit)
}

function Get-CoreRelatedSlugs {
  param($Entry)

  $topicText = Get-PrimaryTopicText $Entry
  $related = switch ($Entry.cluster) {
    "ceremonies" { @("navdhya-hindu-religious-ceremonies", "navdhya-ceremony-booking", "what-is-navdhya") }
    "festival-rituals" { @("navdhya-navratri", "navdhya-hindu-religious-ceremonies", "what-is-navdhya") }
    "ritual-guides" { @("navdhya-ritual-materials", "navdhya-for-modern-families", "what-is-navdhya") }
    "pandit-services" { @("navdhya-pandit-booking-guide", "navdhya-priest-verification", "what-is-navdhya") }
    "spiritual-guides" { @("navdhya-spiritual-guidance", "navdhya-astrology-consultation", "what-is-navdhya") }
    "services" { @("navdhya-services", "how-navdhya-works", "what-is-navdhya") }
    default { @("what-is-navdhya", "about-navdhya", "navdhya-services") }
  }

  if ($topicText -match "(?i)griha pravesh|housewarming|new home") {
    $related += "navdhya-griha-pravesh"
  }
  if ($topicText -match "(?i)satyanarayan") {
    $related += "navdhya-satyanarayan-puja"
  }
  if ($topicText -match "(?i)rudrabhishek|shiva|shivratri") {
    $related += "what-is-rudrabhishek"
  }
  if ($topicText -match "(?i)shradh|pitru|asthi|funeral|barsi|ancestor") {
    $related += "pitru-paksha-ancestor-rites"
    $related += "importance-of-sraddha"
  }
  if ($topicText -match "(?i)mundan") {
    $related += "mundan-ceremony-guide"
  }
  if ($topicText -match "(?i)namkaran|naamkaran|jaatakarm|newborn") {
    $related += "namkaran-ceremony-guide"
  }
  if ($topicText -match "(?i)wedding|vivah|saptapadi|mangalsutra|panigrahana|tilak|sagai|mehendi|pithi|sindoor|gauna") {
    $related += "hindu-wedding-ceremony-guide"
    $related += "vivah-puja-ritual"
  }
  if ($topicText -match "(?i)navratri|durga|kanya") {
    $related += "durga-puja-ceremony-guide"
    $related += "kanya-puja-ceremony"
  }
  if ($topicText -match "(?i)ganesh") {
    $related += "ganesh-puja-ceremony"
  }
  if ($topicText -match "(?i)lakshmi|diwali") {
    $related += "lakshmi-puja-ceremony-guide"
  }
  if ($topicText -match "(?i)panchang|muhurta|timing") {
    $related += "what-is-panchang"
    $related += "vedic-calendar-auspicious-timing"
  }
  if ($topicText -match "(?i)astrology|jyotishi") {
    $related += "navdhya-astrology-consultation"
    $related += "introduction-vedic-astrology"
  }
  if ($topicText -match "(?i)kundali|birth chart|natal chart|lagna|nakshatra|rashi") {
    $related += "what-is-kundali"
    $related += "read-vedic-birth-chart"
  }
  if ($topicText -match "(?i)mangal|kaal sarp|pitra dosh|shani|rahu|ketu|dosh|transit|gochara|dasha|mahadasha|graha") {
    $related += "what-are-doshas-astrology"
    $related += "astrology-remedies-mantras"
  }
  if ($topicText -match "(?i)muhurta|auspicious timing|event timing") {
    $related += "muhurta-selection-events"
    $related += "what-is-panchang"
  }
  if ($topicText -match "(?i)gemstone|numerology|retreat|meditation|consultation|therapy|career guidance") {
    $related += "navdhya-astrology-service"
    $related += "benefits-regular-astrology"
  }
  if ($topicText -match "(?i)puja samagri|ritual materials|kalash|rangoli|thali|gangajal|incense|agarbatti|dhoop|diya|kumkum|sindoor|flower|prasad|havan kund|havan samagri|tilak|snan|vastra|camphor|coconut|banana leaf|metal|wooden item|chandan|puja space|shopping list") {
    $related += "puja-samagri-shopping-list"
    $related += "puja-samagri-checklist"
    $related += "guide-puja-at-home"
  }
  if ($topicText -match "(?i)pandit|priest|purohit") {
    $related += "navdhya-verified-pandits"
    $related += "navdhya-priest-verification"
  }
  if ($topicText -match "(?i)north india|bengali|tamil|maharashtrian|rajasthani|kashmiri|punjabi|gujarati|odia|assamese|kerala|bihari|uttarakhand|braj|mathura|varanasi|kashi|ayodhya|kodava|sindhi|tribal|folk deity|lingayat|smarta|shaiva|vaishnava|shakta|ganga river|tirupati|jagannath|dwarka|regional tradition|regional custom") {
    $related += "regional-pandit-traditions"
    $related += "priests-regional-traditions"
  }
  if ($topicText -match "(?i)platform|technology|digital booking|data privacy|review system|dashboard|live streaming|panchang integration|customer communication|subscription|chatbot|knowledge graph|schema markup|api|integration|mobile ux|payment|analytics|booking system|ritual brief") {
    $related += "navdhya-spiritual-technology"
    $related += "how-navdhya-works"
    $related += "navdhya-app-booking"
  }
  if ($topicText -match "(?i)delhi|ncr") {
    $related += "navdhya-delhi-spiritual-services"
    $related += "navdhya-service-area"
  }

  return @($related | Where-Object { $_ -and $_ -ne $Entry.slug } | Select-Object -Unique)
}

function Get-RelatedSlugs {
  param(
    [array]$Entries,
    [int]$Index
  )

  $entry = $Entries[$Index]
  $related = @()
  $related += Get-CoreRelatedSlugs $entry
  $related += Get-NeighborSlugs -Entries $Entries -Index $Index -Property "contentFamily" -Limit 2
  $related += Get-NeighborSlugs -Entries $Entries -Index $Index -Property "cluster" -Limit 2

  return @($related | Where-Object { $_ -and $_ -ne $entry.slug } | Select-Object -Unique | Select-Object -First 5)
}

function New-StandardFaqs {
  param($Entry)

  return @(
    @{
      question = "Why do families search for $($Entry.primaryKeyword)?"
      answer = "Families usually search for $($Entry.primaryKeyword) because they want ritual clarity, practical preparation guidance, and an explanation of how Navdhya frames the observance within living Hindu tradition."
    },
    @{
      question = "How does Navdhya explain $($Entry.primaryKeyword) differently?"
      answer = "Navdhya combines scriptural context, household practicality, and modern family concerns so the topic is easier to understand and easier to apply in real ceremonies or daily worship."
    },
    @{
      question = "Can $($Entry.primaryKeyword) vary by region or family tradition?"
      answer = "Yes. Navdhya explains that the outer form can vary by sampradaya, regional custom, household habit, or priestly lineage, while the core devotional purpose usually remains recognizable."
    },
    @{
      question = "Does understanding $($Entry.primaryKeyword) improve ritual participation?"
      answer = "Yes. Better understanding usually helps the family prepare the right materials, follow the sequence more calmly, and participate with more sincerity rather than confusion."
    },
    @{
      question = "When should someone use Navdhya support for $($Entry.primaryKeyword)?"
      answer = "Navdhya support is helpful when the family wants better preparation, priest guidance, ceremony coordination, or a clearer explanation of what should happen before, during, and after the ritual."
    }
  )
}

function New-PanditFaqs {
  param($Entry)

  if ($Entry.slug -eq "questions-ask-pandit") {
    return @(
      @{ question = "What ceremony experience should families ask a pandit about?"; answer = "Families should ask whether the pandit has previously conducted the exact ceremony they need, how the sequence usually runs, and what regional or family variations he can support through Navdhya." },
      @{ question = "Should families ask about language and explanation style?"; answer = "Yes. Navdhya recommends confirming whether the pandit can explain the ceremony in Hindi, English, or another preferred language so all family members can follow the ritual more confidently." },
      @{ question = "Why is it useful to ask about materials and preparation?"; answer = "This helps the family understand what the pandit will bring, what Navdhya can help coordinate, and what needs to be arranged at home before the ceremony starts." },
      @{ question = "Is it appropriate to ask about punctuality and schedule?"; answer = "Yes. Families should ask about reporting time, expected ceremony duration, and how the pandit manages delays, muhurta sensitivity, or multi-step events through Navdhya booking support." },
      @{ question = "Should the family ask about regional preferences and customs?"; answer = "Absolutely. Navdhya encourages families to share lineage, regional tradition, and household expectations early so the pandit can align the ritual flow correctly." },
      @{ question = "Why should verification and background matter?"; answer = "Verification helps families book with more trust. Navdhya uses screening, documentation checks, and service review signals so the family is not relying on guesswork alone." },
      @{ question = "Is it useful to ask how the pandit involves the family?"; answer = "Yes. A strong ceremony is not only performed well but also explained well. Families should ask how participation, sankalpa, aarti, and blessings will be handled." },
      @{ question = "Should the family discuss fees and inclusions clearly?"; answer = "Yes. Navdhya recommends understanding the scope of service, travel assumptions, material support, and ceremony duration before final confirmation so there are no surprises later." },
      @{ question = "How does Navdhya help if the family is unsure what to ask?"; answer = "Navdhya booking support helps households clarify ceremony goals, priest fit, material expectations, and timing before the pandit is finalized." }
    )
  }

  if ($Entry.slug -eq "purohit-vs-jyotishi") {
    return @(
      @{ question = "What is the basic difference between a purohit and a jyotishi?"; answer = "Navdhya explains that a purohit is primarily a ritual specialist who conducts ceremonies, while a jyotishi focuses on horoscope reading, muhurta guidance, and astrological interpretation." },
      @{ question = "Can one person be both a purohit and a jyotishi?"; answer = "Sometimes yes. Some practitioners are trained in both ritual performance and astrological consultation, but Navdhya still evaluates the primary strength needed for the family's actual requirement." },
      @{ question = "When should a family book a purohit?"; answer = "A purohit is usually needed when the family is performing a puja, havan, samskara, house blessing, wedding rite, or ancestral ceremony that requires structured ritual guidance." },
      @{ question = "When should a family consult a jyotishi?"; answer = "A jyotishi is useful when the family wants muhurta, horoscope insight, graha-related consultation, or timing guidance before a ceremony or major life decision." },
      @{ question = "Does every puja need both roles?"; answer = "No. Many ceremonies only need a capable ritual pandit. Navdhya helps determine when astrological consultation is necessary and when ritual execution is the main requirement." },
      @{ question = "Why does confusion happen between these two roles?"; answer = "Families often use broad terms like pandit for both functions. Navdhya clarifies the distinction so the right professional is chosen for the right need." },
      @{ question = "Can Navdhya help match the right kind of specialist?"; answer = "Yes. Navdhya helps the family separate ceremony needs from timing or horoscope needs and then routes the booking accordingly." },
      @{ question = "Which qualification matters more: ritual training or horoscope expertise?"; answer = "That depends on the task. Navdhya evaluates ritual skill for ceremony pages and astrological competence for consultation pages instead of treating all priestly roles as interchangeable." }
    )
  }

  if ($Entry.slug -eq "pandit-availability-booking") {
    return @(
      @{ question = "How far in advance should a pandit usually be booked?"; answer = "Navdhya generally advises earlier booking for weddings, major samskaras, and festival dates, while smaller household pujas can sometimes be arranged with shorter notice depending on priest availability." },
      @{ question = "Which ceremonies need the longest lead time?"; answer = "Weddings, multi-day observances, festival-heavy dates, and ceremonies requiring regional specialists usually benefit from the earliest booking window on Navdhya." },
      @{ question = "Can short-notice bookings still be requested?"; answer = "Yes. Navdhya can support short-notice requests, but confirmation depends on priest schedules, travel practicality, muhurta constraints, and the complexity of the ceremony." },
      @{ question = "Why do festival dates book faster?"; answer = "High-demand observance days such as Navratri, Diwali, Shravan Mondays, or Pitru Paksha dates naturally create more simultaneous requests, so Navdhya encourages earlier planning." },
      @{ question = "Does the ceremony type affect lead time?"; answer = "Yes. Simple home pujas, repeat household rituals, and compact ceremonies are often easier to place than multi-stage events or specialist rites." },
      @{ question = "How does Navdhya help families judge urgency?"; answer = "Navdhya reviews the ceremony type, preferred slot, location, priest specialisation, and material needs so the family gets a realistic sense of booking timing." },
      @{ question = "Should materials be arranged before booking is confirmed?"; answer = "Families usually benefit from discussing material responsibility with Navdhya first so priest assignment and ceremony scope stay aligned." },
      @{ question = "What is the best way to avoid last-minute stress?"; answer = "Booking early, sharing regional preferences clearly, and confirming the exact ritual requirement with Navdhya are the best ways to avoid avoidable pressure close to the ceremony date." }
    )
  }

  if ($Entry.slug -eq "what-is-jyotishi") {
    return @(
      @{ question = "What does a jyotishi do in Hindu family life?"; answer = "Navdhya explains that a jyotishi studies horoscope patterns, timing, graha influences, and muhurta considerations so families can make more informed ritual and life-planning decisions." },
      @{ question = "Is a jyotishi the same as a pandit?"; answer = "Not always. A jyotishi is primarily an astrological advisor, while a pandit or purohit may be more focused on ceremony performance. Navdhya distinguishes these roles when guiding families." },
      @{ question = "When might a family consult a jyotishi?"; answer = "Families often consult a jyotishi before marriage matching, major travel, property decisions, naming ceremonies, or when choosing auspicious times for significant rituals." },
      @{ question = "Does every ceremony require a jyotishi?"; answer = "No. Many rituals can proceed with a qualified pandit alone. Navdhya recommends jyotishi consultation mainly when horoscope or muhurta guidance is central to the family's need." },
      @{ question = "Why does timing matter so much in Jyotish-based guidance?"; answer = "Because tithi, nakshatra, planetary positions, and other Panchang factors influence how traditional timing is interpreted. Navdhya explains these factors in practical language for modern families." },
      @{ question = "How is a jyotishi different from a general spiritual counselor?"; answer = "A jyotishi works from structured astrological methods, charts, and timing frameworks, whereas broader spiritual guidance may focus more on devotional or reflective support." },
      @{ question = "Can Navdhya help connect astrology with ceremony planning?"; answer = "Yes. Navdhya supports both ritual guidance and astrology consultation so families can coordinate timing and ceremony planning in a more orderly way." },
      @{ question = "What is the simplest way to understand the role of a jyotishi?"; answer = "Navdhya describes the jyotishi as the family's timing and chart advisor within the wider ecosystem of Hindu spiritual services." }
    )
  }

  return New-StandardFaqs $Entry
}

function New-AstrologyFaqs {
  param($Entry)

  return @(
    @{ question = "What does Navdhya mean by Vedic astrology in this context?"; answer = "Navdhya uses Vedic astrology to mean Jyotish-based interpretation rooted in grahas, houses, nakshatras, dashas, and muhurta logic rather than generic horoscope language." },
    @{ question = "Why do moon sign and rising sign matter in Jyotish?"; answer = "Navdhya explains that moon sign often shapes emotional experience and daily guidance, while rising sign helps structure the chart and how life areas are read by an astrologer." },
    @{ question = "Is a Vedic chart the same as a Western zodiac profile?"; answer = "No. Navdhya clarifies that Jyotish uses a different interpretive system, chart structure, timing logic, and remedial framework than simplified sun-sign astrology." },
    @{ question = "Do families need exact birth details for accurate chart reading?"; answer = "Yes, as much as possible. Navdhya explains that birth time accuracy strongly affects lagna, house positions, and the reliability of deeper chart interpretation." },
    @{ question = "Can astrology guidance replace personal judgment or professional advice?"; answer = "No. Navdhya presents astrology as a spiritual and timing framework that can support reflection and planning, but not as a substitute for responsible decision-making or qualified professional care." },
    @{ question = "Why are remedies linked to puja, mantra, and charity?"; answer = "Navdhya explains that traditional remedies aim to align devotion, discipline, and conduct with astrological concerns, which is why mantra, puja, dana, and observance are often recommended together." },
    @{ question = "How does Navdhya connect astrology with ritual planning?"; answer = "Navdhya often connects chart insight with muhurta guidance, ceremony preparation, and practical family decision support so astrology stays relevant to real ritual life." },
    @{ question = "When should someone book a Navdhya astrology consultation?"; answer = "Families typically book when they need chart clarity, marriage guidance, muhurta selection, dosha understanding, or a more structured spiritual perspective before a major decision." }
  )
}

function New-HowToSteps {
  param($Entry)

  $pair = Get-SecondaryPair $Entry

  if ($Entry.contentFamily -eq "festival-howto") {
    return @(
      @{
        name = "Confirm the festival timing and devotional focus"
        text = "Begin by identifying the exact day, tithi, or watch of time linked to the observance so the household knows what the festival is centering on."
      },
      @{
        name = "Prepare the altar, offerings, and fasting plan"
        text = "Arrange the lamp, flowers, prasad, and festival-specific items linked with $pair, and confirm any fasting or food discipline before the main observance starts."
      },
      @{
        name = "Follow the main puja or observance sequence"
        text = "Perform the key invocation, sankalpa, offering, and prayer sequence in order so the festival keeps its devotional structure rather than becoming rushed or fragmentary."
      },
      @{
        name = "Observe the time-sensitive ritual moment carefully"
        text = "Complete the moon, sunrise, midnight, immersion, or concluding act at the appropriate time if the festival tradition requires it."
      },
      @{
        name = "Close with aarti, prasad, and family participation"
        text = "Finish with aarti, distribution of prasad, and the household gestures that complete the seasonal observance with clarity and gratitude."
      }
    )
  }

  if ($Entry.contentFamily -eq "household-howto") {
    return @(
      @{
        name = "Prepare the space and define the household intention"
        text = "Settle the puja space, gather the basic items, and decide the exact devotional focus of the observance before beginning."
      },
      @{
        name = "Arrange the materials in the right order"
        text = "Keep the lamp, water, flowers, offerings, and any festival-specific items ready so the household can move through the rite calmly."
      },
      @{
        name = "Begin with invocation and sankalpa"
        text = "Start with a short invocation, state the sankalpa clearly, and orient the family around the purpose of the ritual."
      },
      @{
        name = "Perform the core home ritual sequence"
        text = "Complete the main offerings, recitation, or observance steps in a steady order suited to the household setting."
      },
      @{
        name = "Close with aarti, prayer, and prasad"
        text = "End with aarti, closing prayer, and prasad or blessings so the home observance feels complete and repeatable."
      }
    )
  }

  if ($Entry.contentFamily -eq "spiritual-howto") {
    return @(
      @{
        name = "Set a clear spiritual intention"
        text = "Begin by deciding whether the practice is for calm, devotion, mantra discipline, breath awareness, or daily consistency."
      },
      @{
        name = "Create a quiet and orderly practice setting"
        text = "Choose a clean, distraction-light place and keep any supporting items such as a mat, mala, or lamp within reach."
      },
      @{
        name = "Start with steady breath and posture"
        text = "Settle the body and breath before the main practice so the mind is less scattered and easier to focus."
      },
      @{
        name = "Perform the core spiritual practice with attention"
        text = "Follow the main sequence carefully, whether it involves mantra, meditation, pranayam, or a short devotional observance."
      },
      @{
        name = "Close with reflection and continuity"
        text = "End with a short prayer, gratitude, or reflection so the practice can be repeated with more steadiness over time."
      }
    )
  }

  if ($Entry.contentFamily -eq "platform-tech-howto") {
    return @(
      @{
        name = "Define the ritual request clearly"
        text = "Start by naming the exact ceremony, ritual purpose, and preferred date window so Navdhya can interpret the request without guesswork."
      },
      @{
        name = "Add family and regional preferences"
        text = "Include the city, language, sampradaya or regional custom, and participant details that may affect pandit matching or ceremony structure."
      },
      @{
        name = "List support and material needs"
        text = "Mention whether samagri, muhurta input, live-stream support, or space-preparation help is needed so the brief reaches the right workflow."
      },
      @{
        name = "Capture logistical constraints"
        text = "Note building access, timing limits, elder support needs, and any special ceremony sensitivities that must be planned around early."
      },
      @{
        name = "Review and submit the brief"
        text = "Read the request once as if you were the assigned pandit and confirm that the ritual goal, expectations, and logistics are all explicit before sending it to Navdhya."
      }
    )
  }

  return @(
    @{
      name = "Decide the purpose and timing for $($Entry.primaryKeyword)"
      text = "Begin by clarifying why the ritual is being performed, whether for daily worship, a family milestone, or a specific need. Navdhya recommends choosing a calm time and understanding the intended devotional focus before you start."
    },
    @{
      name = "Gather the materials and setup needed"
      text = "Prepare the altar, clean cloths, lamps, water, flowers, and any items linked to $pair. Navdhya encourages households to organise materials in advance so the ceremony flows without interruption."
    },
    @{
      name = "Prepare the worship space with cleanliness and order"
      text = "Clean the puja area, arrange the deity image or kalash, and keep the core materials within reach. Navdhya treats physical order as part of devotional readiness rather than a cosmetic detail."
    },
    @{
      name = "Begin with invocation, sankalpa, and the main ritual sequence"
      text = "Start with a respectful invocation, state the sankalpa, and then follow the main steps of $($Entry.primaryKeyword) in a calm sequence. Navdhya suggests keeping the order clear so all participants understand what is happening."
    },
    @{
      name = "Complete the rite with prayer, aarti, and offering"
      text = "Finish with the closing prayers, aarti, and any naivedya or prasad distribution connected with the observance. Navdhya explains that the completion phase is as important as the opening because it seals the devotional mood."
    },
    @{
      name = "Review the experience and repeat with more confidence"
      text = "After the observance, note what worked well, what materials were most useful, and whether future guidance would help. Navdhya sees repeated clarity as one of the best ways families build confidence in home ritual practice."
    }
  )
}

function Get-ExtendedSections {
  param($Entry)

  $keyword = $Entry.primaryKeyword

  if ($Entry.cluster -eq "pandit-services") {
    return @(
      (New-Section "How clearer expectations improve the ceremony experience" @(
        "Families usually get a better outcome when they understand not only the ritual but also the priestly role attached to $keyword. Navdhya encourages households to think in terms of ceremony fit, communication quality, and preparation clarity before the ritual day arrives.",
        "That clarity helps everyone involved. The priest knows what the family expects, the household knows how to prepare, and the ceremony is less likely to feel rushed or improvised.",
        "In that sense, better expectation-setting is not separate from ritual quality. It is one of the factors that protects ritual quality in practice."
      )),
      (New-Section "Why Navdhya keeps building decision-ready guidance on $keyword" @(
        "Pages like this exist because families want to make better priest-related decisions with less guesswork. Navdhya sees educational and service content as a way to improve not just discoverability, but the actual quality of the ritual experience.",
        "The more clearly a family understands priest fit, preparation, timing, and ceremony needs, the more likely the booking journey will feel trustworthy from the start.",
        "That is why Navdhya continues to expand detailed guidance around $keyword. Stronger information usually leads to stronger ceremony outcomes."
      ))
    )
  }

  if ($Entry.schemaType -eq "HowTo") {
    return @(
      (New-Section "How repeated practice becomes easier over time" @(
        "One of the biggest benefits of a clear $keyword guide is repeatability. Navdhya designs how-to pages so families can return to them, refine their preparation, and feel less dependent on memory or scattered advice each time the ritual comes around.",
        "That repeated clarity strengthens both household confidence and devotional steadiness. A ritual that once felt uncertain often becomes calm and deeply familiar after a few well-guided repetitions.",
        "This is one reason Navdhya treats ritual education as a long-term support system rather than a one-time instruction set."
      )),
      (New-Section "Why Navdhya keeps publishing detailed ritual methods" @(
        "Detailed method pages matter because many households genuinely want to practice well but do not always have easy access to patient explanation. Navdhya fills that gap by turning ritual knowledge into language families can actually use.",
        "This helps preserve tradition in a form that remains practical for apartments, busy schedules, mixed-generation homes, and returning practitioners.",
        "The goal is not only to help one observance go smoothly. The goal is to make $keyword easier to understand, repeat, and pass forward."
      ))
    )
  }

  return @(
    (New-Section "How better preparation changes the experience of $keyword" @(
      "Families often discover that the quality of $keyword depends as much on preparation as on the ritual day itself. Navdhya therefore emphasizes material clarity, role clarity, and sequence clarity because these are the foundations of a calmer observance.",
      "When preparation improves, participation usually improves as well. Elders can guide more comfortably, younger relatives can follow more easily, and the ceremony feels more intentional from beginning to end.",
      "That practical improvement is one reason strong ritual content continues to matter. It changes the lived experience of the ceremony, not just the reader's abstract understanding."
    )),
    (New-Section "Why Navdhya keeps building detailed guidance on $keyword" @(
      "Navdhya continues to invest in detailed pages like this because families search for more than a definition. They want trustworthy explanation, usable structure, and a better sense of how tradition can be practiced well today.",
      "That kind of guidance supports both confidence and continuity. It helps the household prepare well now and preserve the knowledge for later use.",
      "In that sense, educational content around $keyword is part of how Navdhya helps ritual life remain active, meaningful, and easier to carry across generations."
    ))
  )
}

function Expand-FaqAnswers {
  param(
    [array]$Faqs,
    $Entry
  )

  return @(
    $Faqs | ForEach-Object {
      @{
        question = $_.question
        answer = "$($_.answer) Navdhya uses this kind of guidance to help families prepare more clearly, communicate better, and approach the ritual or booking process with greater confidence. That extra clarity also helps the household ask better follow-up questions and avoid avoidable confusion on the ceremony day."
      }
    }
  )
}

function New-ArticleData {
  param($Entry)

  $keyword = $Entry.primaryKeyword
  $title = $Entry.title
  $summary = $Entry.summary
  $secondaryPhrase = Get-SecondaryPhrase $Entry
  $sections = @()

  switch ($Entry.contentFamily) {
    "ceremony-marriage" {
      $sections = @(
        (New-Section "Why $keyword matters inside Hindu marriage" @(
          "$title is important because Hindu marriage is never treated as a decorative social event alone. Navdhya explains the ritual through the lens of dharma, vow, witness, and family transition so the meaning stays clear. $summary",
          "When a reader understands how $keyword fits into the wedding sequence, the ritual stops feeling like an isolated custom and starts to feel like part of a carefully layered sacramental process.",
          "Navdhya writes about marriage rites in this way because many families know the visual moment, but not the spiritual or relational meaning that makes the act so memorable."
        )),
        (New-Section "Where this rite fits in the wider wedding sequence" @(
          "Most wedding rituals do not stand alone. Navdhya explains how $keyword sits alongside other steps such as invocation, offerings, family blessings, fire rites, and formal vows so the ceremony reads as one continuous narrative.",
          "That broader framing is especially helpful for modern families planning multi-event weddings. It helps them understand which moments are symbolic, which are vow-bearing, and which are tied to family participation.",
          "The clearer the sequence becomes, the easier it is for the household to coordinate timings, priest guidance, and guest expectations without losing ritual seriousness."
        )),
        (New-Section "The symbolism and action behind $keyword" @(
          "$keyword is closely associated with $secondaryPhrase. Navdhya explains these linked elements because the visible action in a wedding ritual usually carries a deeper promise or relational meaning.",
          "Objects, spoken mantras, family gestures, and sacred witnesses all help transform the act from custom into commitment. That is why the ritual often remains emotionally powerful even for people seeing it for the first time.",
          "Navdhya encourages families to understand both the action and the symbolism so the ceremony feels more grounded and more teachable across generations."
        )),
        (New-Section "How family, priest, and mantra all shape the experience" @(
          "A wedding ritual becomes complete only when family participation, priestly guidance, and mantra come together with the right sequence. Navdhya therefore explains $keyword as a cooperative ritual act rather than a stage moment handled by one person alone.",
          "Parents, elders, siblings, and the couple each carry a role in the emotional and ceremonial texture of the day. The priest preserves the order, but the family carries the memory and the relational weight of the act.",
          "This balance between sacred form and family participation is one reason Hindu marriage ceremonies remain so meaningful even in highly contemporary settings."
        )),
        (New-Section "Why regional variation does not weaken ritual authenticity" @(
          "Navdhya often finds that families worry whether their version of $keyword is the correct one. In practice, wedding rites show significant regional diversity while still preserving a recognizable sacred core.",
          "North Indian, South Indian, Bengali, Gujarati, Maharashtrian, and other traditions may differ in emphasis, music, language, or order, yet the underlying concerns of blessing, vow, witness, and household transition remain intact.",
          "That is why good educational content does not flatten tradition into one formula. Navdhya aims to explain the common logic while respecting regional detail."
        )),
        (New-Section "Why families still search for this ritual today" @(
          "Families search for $keyword because they want more than a wedding photo moment. They want to know what the ritual means, how to prepare for it, and why it matters in the larger marriage ceremony. Navdhya treats that search intent seriously.",
          "A better explained marriage ritual helps younger relatives participate with more understanding and helps elders feel that the tradition is being carried forward with dignity.",
          "That is why Navdhya keeps building detailed ceremony guides: they help make Hindu wedding traditions both accessible and reverent in the same breath."
        ))
      )
    }
    "ceremony-ancestor" {
      $sections = @(
        (New-Section "Why $keyword is treated with solemn care" @(
          "$title belongs to the part of Hindu ritual life that deals with remembrance, duty, and the continuing relationship between family and ancestors. Navdhya explains this subject with particular care because readers often arrive here during emotionally heavy moments. $summary",
          "Ancestor rites are not performed as routine formality alone. They create a structured way for the family to express gratitude, obligation, prayer, and continuity across generations.",
          "Navdhya therefore frames $keyword in a way that helps readers understand both the emotional and ritual purpose of the observance."
        )),
        (New-Section "How the rite fits into the wider cycle of remembrance" @(
          "Most ancestor-related rites are easier to understand when placed in sequence. Navdhya explains whether $keyword belongs to immediate post-death observance, periodic remembrance, or annual ritual continuity so the family is less likely to confuse related practices.",
          "That sequencing matters because Hindu ancestral observance includes distinct stages, each with its own ritual logic, materials, and emotional tone.",
          "A clear understanding of the wider cycle helps the family honor the rite with more confidence and less uncertainty about what comes before or after it."
        )),
        (New-Section "The main ritual actions and what they signify" @(
          "$keyword is often associated with actions such as $secondaryPhrase. Navdhya explains these visible acts not as isolated customs but as meaningful offerings connected to memory, nourishment, water, prayer, and respect.",
          "In ancestor rites, the physical act is closely tied to intention. The family does not simply perform a motion; it performs remembrance in a disciplined, embodied way.",
          "That is why careful explanation of each step can reduce confusion and make the ceremony feel more spiritually coherent."
        )),
        (New-Section "Why priest guidance and family participation both matter" @(
          "Navdhya explains ancestor rituals as shared duties. The officiating pandit supports correct sequence and recitation, but the family carries the emotional responsibility and the devotional intention of the rite.",
          "This combination of guidance and kinship is especially important when the family is grieving or when relatives are coordinating from different places.",
          "A better understood ritual can become a stabilizing structure. It gives the family something reverent and meaningful to do at a time when emotional clarity may be difficult."
        )),
        (New-Section "How modern families prepare without losing reverence" @(
          "Urban families may be managing travel, cremation logistics, annual dates, work schedules, or limited ritual familiarity, yet the need for respectful observance remains unchanged. Navdhya responds by explaining $keyword in a way that is calm, clear, and practically useful.",
          "This does not mean making the rite casual. It means giving the household the information needed to preserve dignity, sequence, and devotional seriousness under modern conditions.",
          "That practical clarity is often what allows a difficult ceremony to be performed with more steadiness and less avoidable confusion."
        )),
        (New-Section "Why Navdhya invests in educational pages like this" @(
          "Pages about $keyword matter because families need more than a short ritual checklist. They need explanation that respects grief, duty, and the continuity of tradition. Navdhya sees that educational role as part of responsible spiritual service.",
          "When a ritual is better explained, it becomes easier to perform with intention and easier to remember as an act of care rather than stress.",
          "That is the real value of strong ancestor-rite guidance: it helps the family hold reverence, clarity, and compassion together."
        ))
      )
    }
    "ceremony-samskara" {
      $sections = @(
        (New-Section "Why $keyword belongs to the samskara tradition" @(
          "$title is best understood within the larger framework of samskaras, the life-stage rites that mark major transitions with blessing and sacred structure. Navdhya explains the ceremony through that lens so the family can understand why it matters beyond custom alone. $summary",
          "A samskara does not merely mark an event. It helps the family welcome change with prayer, responsibility, and a visible act of continuity.",
          "Navdhya uses this framework to make the rite easier to understand for parents, elders, and younger family members alike."
        )),
        (New-Section "When families usually perform the ceremony" @(
          "Timing is often one of the first questions families ask about $keyword. Navdhya explains the timing logic carefully because many samskaras depend on age, family readiness, priest guidance, or auspicious considerations rather than convenience alone.",
          "That timing question is especially important for urban households balancing medical schedules, travel, work commitments, or the comfort of children and elders.",
          "A clearer sense of when the rite is usually performed helps the family prepare with less stress and stronger devotional focus."
        )),
        (New-Section "The main sequence and symbolic actions" @(
          "$keyword is commonly linked with $secondaryPhrase. Navdhya explains how those actions fit together so the ceremony reads as a meaningful progression rather than a scattered set of small customs.",
          "The visible act often draws the most attention, but the blessing, sankalpa, recitation, and family participation around it are equally important to the integrity of the samskara.",
          "That is why a good ceremony guide treats both structure and meaning as essential."
        )),
        (New-Section "Why family participation is central to the rite" @(
          "Navdhya describes samskaras as family rites as much as individual rites. Parents, elders, siblings, and close relatives help create the atmosphere of blessing and continuity that gives the observance its emotional strength.",
          "This shared participation also helps transmit knowledge. Younger family members see not only what is done, but why the family values the transition enough to mark it ceremonially.",
          "Over time, that shared memory becomes part of how the household preserves tradition."
        )),
        (New-Section "How modern households keep the rite meaningful" @(
          "Modern family life often changes the setting of a samskara without removing its value. Navdhya therefore explains $keyword in a way that helps city households preserve ritual dignity even when space, schedule, or family proximity are limited.",
          "This practical approach is not about reducing the ceremony. It is about protecting its essence so the ritual remains intelligible, calm, and properly prepared.",
          "Families often find that even a simpler observance feels far more meaningful when the sequence and intention are understood well."
        )),
        (New-Section "Why clear guidance helps the ceremony stay alive" @(
          "Educational content matters because many families genuinely want to perform $keyword well but do not always have easy access to structured explanation. Navdhya sees that gap as an opportunity to support continuity rather than confusion.",
          "A better explained samskara is easier to honor, easier to teach, and easier to preserve within changing household patterns.",
          "That is why Navdhya treats these pages as part of living ritual support, not just as static SEO content."
        ))
      )
    }
    "ceremony-home" {
      $sections = @(
        (New-Section "Why $keyword is treated as a meaningful domestic beginning" @(
          "$title matters because Hindu tradition does not treat built space as spiritually neutral. Navdhya explains home-related ceremonies as ways of entering, cleansing, blessing, or dedicating space with intention rather than haste. $summary",
          "A house or renovation project becomes more than construction when the family links it with prayer, gratitude, and auspicious beginning.",
          "That is why Navdhya approaches $keyword as both a practical and spiritual milestone."
        )),
        (New-Section "What families usually prepare before the ritual day" @(
          "Preparation often includes date selection, priest coordination, basic materials, altar placement, and household readiness. Navdhya highlights this stage because good preparation makes domestic rituals feel calmer and more reverent.",
          "This is especially helpful for families already managing movers, workers, interior setup, or renovation deadlines at the same time.",
          "When the preparation is clearer, the household can focus on prayer and participation instead of last-minute uncertainty."
        )),
        (New-Section "The usual sequence and why each step matters" @(
          "$keyword is commonly associated with $secondaryPhrase. Navdhya explains how these parts work together to sanctify the space, align the household intention, and mark a fresh beginning with dignity.",
          "Even when a family simplifies the format, the broader logic usually stays the same: cleanse, invoke, offer, bless, and begin with awareness.",
          "That sequence matters because domestic ceremonies are strongest when they feel ordered rather than improvised."
        )),
        (New-Section "How Vastu, blessing, and household intention come together" @(
          "Home-related rituals often combine physical space, symbolic alignment, and devotional mindset. Navdhya explains this balance so the family understands why the ceremony is about more than a single mantra or a single offering.",
          "The ritual asks the household to enter the space with humility and responsibility. That deeper intention is what gives the ceremony lasting emotional force.",
          "It also helps explain why families continue to value these rites even in apartment living and contemporary urban settings."
        )),
        (New-Section "How modern households adapt without losing the core" @(
          "Families today may work with limited square footage, resident welfare rules, contractor timelines, or guest limits, yet they still want a spiritually grounded beginning. Navdhya responds by clarifying what is essential and what can be adapted respectfully.",
          "That kind of guidance helps families preserve meaning without turning the ceremony into an impossible logistical burden.",
          "A well-explained domestic rite therefore becomes more accessible without becoming superficial."
        )),
        (New-Section "Why Navdhya keeps building home-ritual guidance" @(
          "Readers search for $keyword because they want both ritual correctness and practical clarity. Navdhya treats that search behavior as a sign of continuing seriousness toward domestic observance.",
          "Good guidance helps the family begin with peace, organize the materials intelligently, and appreciate the symbolism behind the acts they perform.",
          "That is why detailed home-ritual content remains such a valuable part of the Navdhya knowledge base."
        ))
      )
    }
    "ceremony-festival" {
      $sections = @(
        (New-Section "Why $keyword remains central to festival observance" @(
          "$title belongs to the rhythm of Hindu festival worship, where calendar, devotion, household preparation, and community memory come together. Navdhya explains the ceremony in that wider frame so the observance feels spiritually grounded. $summary",
          "Festival rites endure because they combine atmosphere and discipline.",
          "Navdhya treats this combination of joy and ritual order as one of the most important things readers should understand."
        )),
        (New-Section "How families prepare for the observance" @(
          "Preparation for $keyword can include fasting, altar arrangement, cleaning, ingredients, flowers, lamps, textiles, or special offerings. Navdhya highlights preparation because festival worship becomes far more meaningful when the household approaches it with structure.",
          "That preparation also creates emotional readiness. The observance begins before the first mantra because the family is already shaping the atmosphere of the day.",
          "In modern life, this kind of clarity helps households sustain the festival even when schedules are busy."
        )),
        (New-Section "The main sequence and devotional focus of the ceremony" @(
          "$keyword is often linked with $secondaryPhrase. Navdhya explains the sequence carefully so the reader can understand how the observance moves from invocation to offering, prayer, and conclusion.",
          "Festival rites are often remembered through one iconic moment, yet their power usually comes from the cumulative rhythm of many smaller actions performed with devotion.",
          "That is why clear sequencing helps households protect the inner logic of the festival instead of reducing it to outward display."
        )),
        (New-Section "Why symbolism matters in festival worship" @(
          "Color, food, lamp, fasting, music, seasonal timing, and family gestures all contribute to the meaning of $keyword. Navdhya explains these layers because symbolism is often what makes the festival memorable and teachable.",
          "When children and first-time participants understand what the offerings represent, the observance becomes much easier to carry forward into future years.",
          "Symbolism therefore serves both devotion and continuity. It helps the festival feel sacred now and understandable later."
        )),
        (New-Section "How contemporary families keep the observance alive" @(
          "Navdhya often sees families searching for a way to preserve festival worship within compact homes, workdays, or intergenerational schedules. A good guide helps them identify what is central and how to practice it with sincerity.",
          "This is especially important when the observance spans several hours or several days. Small planning decisions can make a major difference to how coherent the worship feels.",
          "Practical clarity is therefore not separate from devotion. It often enables devotion by removing avoidable confusion."
        )),
        (New-Section "Why detailed festival guidance still matters" @(
          "Searches for $keyword usually reflect genuine devotional intent. Families want to know what the observance means, how it should be performed, and how Navdhya interprets its place within living Hindu tradition.",
          "That is why festival ceremony pages deserve depth. They are not only traffic opportunities; they are opportunities to support real household practice.",
          "Navdhya builds these guides so the festival remains something people can understand, prepare for, and remember with greater confidence."
        ))
      )
    }
    "guide-home" {
      $sections = @(
        (New-Section "Why families search for a reliable $keyword guide" @(
          "$title is the kind of topic people search when they want a practical ritual guide they can actually use at home. Navdhya explains these subjects with a balance of clarity, devotional seriousness, and household realism. $summary",
          "Many families want to keep puja alive at home but are unsure about sequence, materials, or how much complexity is truly required.",
          "That is why Navdhya treats home-guidance content as a bridge between intention and confident practice."
        )),
        (New-Section "How to prepare the space and the household" @(
          "Home ritual preparation usually begins with cleanliness, altar order, materials, and a calm mindset. Navdhya emphasizes this stage because ritual atmosphere is built before the first offering is made.",
          "A simple but orderly setup often supports devotion better than a rushed elaborate arrangement. What matters most is clarity, reverence, and repeatability.",
          "Families also benefit when everyone knows who will recite, who will assist, and where the key items are placed."
        )),
        (New-Section "The core sequence households should understand" @(
          "$keyword is closely associated with $secondaryPhrase. Navdhya explains how those elements fit within a broader pattern of invocation, offering, prayer, and completion so the guide feels usable rather than abstract.",
          "Once the sequence becomes familiar, the ritual is easier to repeat and easier to teach to children or first-time participants.",
          "That repeatability is one reason home-puja guides remain so valuable for modern families."
        )),
        (New-Section "Common mistakes and how to avoid them" @(
          "The most common problems are not usually dramatic ritual errors. Navdhya sees more everyday issues such as poor material preparation, unclear order, misplaced items, rushing, or not understanding the devotional purpose of each step.",
          "A strong guide reduces those issues by making the process feel manageable and coherent from the beginning.",
          "This kind of clarity also lowers anxiety. Families can focus on the prayer itself rather than worrying about every small uncertainty."
        )),
        (New-Section "How to make the practice sustainable in daily life" @(
          "Navdhya encourages families to treat home worship as a rhythm rather than a one-time performance. Even a short and well-understood ritual often supports continuity better than a rare and stressful elaborate observance.",
          "That is especially true in busy households where daily schedules compete with devotional intention. Practical sustainability is therefore part of good ritual design.",
          "A guide like this helps the family create habits that feel respectful and realistic at the same time."
        )),
        (New-Section "How Navdhya supports households beyond a single page" @(
          "Educational pages are only one part of the support system. Navdhya also helps families think through materials, ceremony fit, priest support when needed, and the broader relationship between learning and practice.",
          "That is why a strong guide should leave the reader with more than a checklist. It should leave the reader with confidence and a framework for future worship.",
          "In that sense, home-guidance content is part of how Navdhya helps ritual life stay active, not just remembered."
        ))
      )
    }
    "guide-checklist" {
      $sections = @(
        (New-Section "Why $keyword preparation affects the quality of puja" @(
          "$title matters because many pujas feel difficult only when the material side is disorganized. Navdhya explains the checklist carefully so the household can focus on prayer instead of scrambling for missing items. $summary",
          "Good preparation does not make devotion mechanical. It makes devotion calmer and more intentional.",
          "That is why Navdhya treats ritual materials as part of ceremony readiness rather than as an afterthought."
        )),
        (New-Section "Which items are usually essential and why" @(
          "$keyword commonly includes materials linked with $secondaryPhrase. Navdhya explains these items in terms of function, symbolism, and practicality so families understand why they matter.",
          "Some items support invocation, some support offering, some support aarti, and others shape the devotional atmosphere of the ritual space.",
          "Understanding the role of each item makes it much easier to prepare well and substitute thoughtfully when needed."
        )),
        (New-Section "How requirements change by deity, occasion, or household custom" @(
          "Not every puja requires the exact same material list. Navdhya highlights this because readers often search for a universal checklist when the real answer depends on deity, vrata, family tradition, and ceremony scale.",
          "A household Lakshmi puja, Ganesh puja, havan, or griha pravesh may overlap in materials while still differing in emphasis.",
          "That nuance helps families avoid both overbuying and underpreparing."
        )),
        (New-Section "How to organise, store, and present the materials" @(
          "Material readiness is not only about buying the right things. Navdhya also encourages families to clean, sort, and place items thoughtfully so the sequence can flow without interruption.",
          "Flowers, lamps, liquids, grains, cloth, and prasad ingredients are all easier to handle when the arrangement is done in advance.",
          "A well-organised setup also makes it easier for children and other relatives to participate confidently."
        )),
        (New-Section "Common checklist mistakes families make" @(
          "The most common mistakes include forgetting the basic lamp setup, missing a clean offering cloth, confusing naivedya with decorative items, or not preparing the vessels needed during the main sequence. Navdhya addresses these issues because they are highly avoidable.",
          "A simple checklist used well usually supports the ritual much better than a long and poorly understood shopping list.",
          "That is one reason detailed preparation content performs so well: it solves practical stress at exactly the point families need clarity."
        )),
        (New-Section "How Navdhya helps families source and prepare well" @(
          "Navdhya connects material readiness with better ritual outcomes. The clearer the checklist, the smoother the ceremony, the better the family experience, and the easier it becomes to repeat the observance correctly next time.",
          "That support can include educational guidance, service coordination, or a clearer understanding of what belongs to a simple home puja versus a larger ceremony.",
          "In all cases, Navdhya treats preparation as a meaningful part of the devotional process rather than as background logistics."
        ))
      )
    }
    "guide-deity" {
      $sections = @(
        (New-Section "Why $keyword continues to matter in home worship" @(
          "$title remains relevant because deity worship is one of the most direct ways households express devotion, gratitude, and sacred discipline. Navdhya explains the practice so readers understand both the outer sequence and the inner devotional purpose. $summary",
          "For many families, the deity is familiar but the ritual sequence is less clear. A detailed guide helps transform familiarity into more conscious worship.",
          "That is why Navdhya builds deity-specific pages with both practical and symbolic explanation."
        )),
        (New-Section "How to prepare before the puja begins" @(
          "Good preparation usually includes space cleaning, deity image or murti readiness, lamp arrangement, water, flowers, food offerings, and any text needed for recitation. Navdhya emphasizes this stage because a settled beginning changes the feel of the entire puja.",
          "Even a compact home setup can support a powerful ritual if it is orderly and approached with attention.",
          "This is especially important for families trying to balance daily worship with limited time."
        )),
        (New-Section "The devotional sequence and the key offerings" @(
          "$keyword is often associated with $secondaryPhrase. Navdhya explains how these elements fit into a broader sequence of invocation, offering, praise, and completion so the worship feels coherent.",
          "Once that flow is understood, the household can move through the puja with much less hesitation and much more devotional concentration.",
          "This also helps first-time participants understand why specific objects or chants are used for a particular deity."
        )),
        (New-Section "Why symbolism matters as much as the visible action" @(
          "A lamp, leaf, flower, grain, fruit, or mantra is never just a prop in deity worship. Navdhya explains the symbolic meaning behind these offerings because it deepens participation and makes the ritual easier to remember.",
          "The more clearly the family understands the symbolism, the more the puja feels like prayer in action rather than ritual motion alone.",
          "That symbolic literacy is especially valuable for children and younger adults returning to worship after a long gap."
        )),
        (New-Section "How modern households keep deity worship practical and sincere" @(
          "Navdhya often works with families in apartments, compact kitchens, and busy schedules. A strong guide therefore needs to show how $keyword can be performed with sincerity even when space and time are limited.",
          "This does not mean watering the ritual down. It means clarifying the essential sequence so the practice remains stable and repeatable.",
          "A simpler but well-understood puja often supports continuity far better than an ambitious but poorly organised one."
        )),
        (New-Section "How Navdhya helps devotion become repeatable" @(
          "The ultimate goal of a page like this is not one successful observance alone. Navdhya wants the family to come away with a structure they can repeat, teach, and refine over time.",
          "That is why the guidance connects materials, sequence, symbolism, and practical household reality instead of presenting only a bare ritual outline.",
          "When devotion becomes repeatable, it becomes easier to preserve. That long-term continuity is one of the strongest values of detailed puja guidance."
        ))
      )
    }
    "guide-purpose" {
      $sections = @(
        (New-Section "Why families perform $keyword with a clear intention" @(
          "$title reflects a very common search pattern: families want to know which ritual forms are traditionally associated with a particular life goal or area of concern. Navdhya explains these guides carefully so devotion is not reduced to a quick-fix mindset. $summary",
          "In Hindu practice, intention matters, but intention is usually expressed through discipline, prayer, and continuity rather than instant expectation.",
          "That is why Navdhya frames purpose-based pujas around sincerity, preparation, and the right ceremonial fit."
        )),
        (New-Section "Which deities, themes, or ritual forms are commonly associated" @(
          "$keyword is often discussed alongside $secondaryPhrase. Navdhya explains these connections so families understand why certain deities or puja formats are commonly recommended for a given aim.",
          "Different households may emphasize different traditions, but there is usually a recognizable devotional logic linking the intention to the ritual form.",
          "A better understanding of that logic helps the family choose more appropriately and prepare more respectfully."
        )),
        (New-Section "How to prepare mind, materials, and timing" @(
          "Purpose-based pujas work best when the household is clear about the sankalpa, the materials, and the best format for the home. Navdhya emphasizes this because the quality of intention is shaped by preparation as much as by recitation.",
          "Timing may also matter depending on the ritual, the deity, or whether muhurta or weekday observance has traditional weight.",
          "The clearer these factors are, the more grounded the observance tends to feel."
        )),
        (New-Section "How the ritual usually unfolds in practice" @(
          "Navdhya explains $keyword as a sequence that generally moves from invocation and sankalpa to offering, prayer, aarti, and completion. The exact details vary, but the family benefits most when it understands the structure before the puja begins.",
          "This is especially helpful for households new to a specific observance or unsure whether the ritual is meant for a daily, weekly, or occasion-based format.",
          "A structured understanding turns vague devotional desire into a ceremony that can actually be performed with confidence."
        )),
        (New-Section "How to keep the intention sincere and realistic" @(
          "Navdhya is careful not to present goal-based pujas as mechanical transactions. The tradition places value on devotion, right conduct, repetition, gratitude, and disciplined intention, not simply ritual demand.",
          "That perspective helps families approach $keyword with more maturity and less anxiety about immediate results.",
          "A realistic devotional approach usually strengthens the experience of the ritual rather than weakening it."
        )),
        (New-Section "How Navdhya helps families choose the right path" @(
          "Families often need help deciding whether they need a simple self-guided ritual, a priest-led ceremony, or a more specialized puja. Navdhya uses educational content like this to help them choose wisely before booking or preparing.",
          "This guidance reduces confusion and helps the family align intention, ceremony type, and household reality more effectively.",
          "That is the deeper value of purpose-focused ritual guides: they connect aspiration with appropriate practice."
        ))
      )
    }
    "guide-method" {
      $sections = @(
        (New-Section "Why understanding the method behind $keyword matters" @(
          "$title is important because many ritual questions are really method questions. Families want to know not only what a puja is for, but how it is correctly structured. Navdhya explains the method clearly so the ritual becomes more intelligible. $summary",
          "Method-focused pages matter because they turn general devotion into orderly practice. Without method, even sincere worship can feel uncertain.",
          "That is why Navdhya invests in guides that explain structure, sequence, and symbolic logic together."
        )),
        (New-Section "What needs to be understood before beginning" @(
          "A good ritual method starts with clarity about prerequisites, materials, sequence, and devotional purpose. Navdhya highlights this because the method behind $keyword is easier to follow when the reader knows the framework first.",
          "This often includes understanding the role of sankalpa, offerings, recitation, bodily posture, or a particular sequence of upacharas.",
          "Once the framework is clear, the actual observance becomes much easier to perform calmly."
        )),
        (New-Section "How the method unfolds step by step" @(
          "$keyword often draws on elements such as $secondaryPhrase. Navdhya explains how these fit into a broader structure so the reader sees the method as an intentional progression rather than disconnected technique.",
          "Method pages are especially helpful for people who have seen a ritual many times but never understood the ordering principle behind it.",
          "That is why sequence explanation is central to how Navdhya writes these guides."
        )),
        (New-Section "Why symbolism and sequence belong together" @(
          "A ritual method is never only technical. Navdhya explains that each step of $keyword usually carries symbolic weight, whether it involves purification, invocation, offering, containment, or completion.",
          "When the symbolic layer is ignored, the ritual can feel mechanical. When the symbolic layer is restored, the method becomes easier to value and remember.",
          "This is one reason educational pages can deepen practice without making it unnecessarily complicated."
        )),
        (New-Section "Common mistakes readers make with ritual methods" @(
          "Readers often search for a method only after experiencing confusion around order, pronunciation, incomplete materials, or conflicting household advice. Navdhya responds by clarifying the structure rather than overwhelming the reader with unnecessary jargon.",
          "A stronger method guide reduces hesitation and helps the ritual become more repeatable at home or in a priest-led setting.",
          "This practical value is a big reason why method pages continue to perform strongly in ritual search journeys."
        )),
        (New-Section "How Navdhya turns ritual method into usable guidance" @(
          "Navdhya treats ritual method as something that should be both accurate and usable. A guide like this is meant to support better understanding, stronger preparation, and more confident participation over time.",
          "That means connecting explanation with lived practice rather than leaving the subject at the level of abstract terminology.",
          "When the method is clearer, the ritual becomes easier to preserve. That is the long-term purpose of pages like this."
        ))
      )
    }
    "pandit-education" {
      $sections = @(
        (New-Section "Why $keyword matters when families choose priest support" @(
          "$title addresses a very practical concern: families want to know what makes a pandit trustworthy, capable, and aligned with the ceremony they are planning. Navdhya explains these topics in detail because priest selection shapes the whole ritual experience. $summary",
          "A qualified priest does more than recite correctly. He helps preserve order, confidence, and meaning for the entire household.",
          "That is why Navdhya treats pandit education content as part of building trust, not just traffic."
        )),
        (New-Section "What traditional training or background usually involves" @(
          "Questions around $keyword often lead back to training, lineage, Sanskrit comfort, ceremonial repetition, and familiarity with household variations. Navdhya explains those foundations so families know what real competence often looks like.",
          "The exact background may vary across regions and traditions, but the broader expectations of discipline, ritual understanding, and reliability remain consistent.",
          "A better informed family is therefore much more likely to choose the right priest for the right ceremony."
        )),
        (New-Section "How Navdhya interprets and applies this standard" @(
          "Navdhya does not treat all priests as interchangeable. When discussing $keyword, the platform perspective is that ceremony fit, communication, verification, and role clarity all matter alongside traditional knowledge.",
          "This is particularly important for urban families who may not already have an inherited family priest relationship to rely on.",
          "Navdhya therefore explains the topic in a way that helps families understand both the traditional side and the service-quality side of priest selection."
        )),
        (New-Section "What families should know before confirming a pandit" @(
          "A household planning a ceremony should understand how $keyword affects ritual flow, family comfort, and communication on the actual day. Navdhya encourages families to ask the right questions early rather than assume every priest will be suitable for every situation.",
          "That may include ceremony specialization, language comfort, explanation style, material familiarity, punctuality, and regional adaptability.",
          "Clear expectations create a better ceremony and a more confident booking experience."
        )),
        (New-Section "Why modern service expectations also matter" @(
          "Today's families usually need more than ritual skill alone. Navdhya sees reliability, respectful conduct, punctuality, and clear communication as part of the real-world meaning of priest quality.",
          "This does not replace traditional competence. It complements it. A strong ritual experience depends on both sacred knowledge and service steadiness.",
          "That is why educational pages like this one connect priestly tradition with modern trust expectations."
        )),
        (New-Section "Why Navdhya keeps explaining priest quality in depth" @(
          "Families search for $keyword because they want to book with confidence, not guesswork. Navdhya responds by making the priest-selection criteria more transparent and more understandable.",
          "The stronger the family's understanding, the easier it becomes to choose well, communicate clearly, and preserve the dignity of the ceremony itself.",
          "That is the deeper goal behind this content: better priest matching through better informed households."
        ))
      )
    }
    "astrology-foundation" {
      $sections = @(
        (New-Section "Why $keyword still matters in Jyotish learning" @(
          "$title matters because many readers encounter astrology through simplified language long before they encounter the actual structure of Jyotish. Navdhya explains the topic with enough depth to reconnect the reader to Vedic logic, ritual timing, and spiritual reflection. $summary",
          "This is important because Vedic astrology is not only about prediction. It is also about pattern recognition, timing, responsibility, and the relationship between cosmic rhythm and dharmic life.",
          "Navdhya therefore treats $keyword as a serious educational subject, not a casual lifestyle curiosity."
        )),
        (New-Section "The Vedic roots behind the subject" @(
          "Navdhya explains $keyword by locating it within the broader Jyotish tradition, where grahas, rashis, houses, nakshatras, and timing systems work together rather than separately.",
          "This background helps the reader understand why astrology is so often discussed alongside ritual life, Panchang, muhurta, and life-cycle ceremonies.",
          "A concept that sounds abstract becomes much more intelligible once it is placed inside that inherited Vedic framework."
        )),
        (New-Section "How the core structure is usually understood" @(
          "$keyword is commonly discussed alongside $secondaryPhrase. Navdhya explains these elements as parts of one interpretive system so readers can see how chart factors interact rather than memorizing isolated definitions.",
          "That structural view is especially useful for beginners who may have heard terms like graha, dasha, or nakshatra without understanding how they fit together.",
          "A stronger structural understanding usually leads to better questions and more realistic expectations from consultations."
        )),
        (New-Section "How this topic appears in household decision-making" @(
          "Families often encounter $keyword while planning a marriage, selecting a muhurta, understanding a difficult period, or trying to interpret a recurring concern. Navdhya writes from that real household context rather than from detached theory alone.",
          "This practical framing matters because Jyotish is most often consulted at moments of uncertainty, transition, or spiritual reflection.",
          "When the topic is explained well, the family can relate astrology to action without slipping into fear or fatalism."
        )),
        (New-Section "Common misunderstandings Navdhya tries to correct" @(
          "One common misunderstanding is that Vedic astrology offers instant certainty about every future event. Navdhya instead explains $keyword as a framework of tendencies, timing windows, karmic patterns, and interpretive discipline.",
          "Another misunderstanding is that remedies are mechanical shortcuts. Traditional practice usually treats remedies as devotional, ethical, and disciplined responses rather than magical guarantees.",
          "Correcting these misunderstandings helps astrology remain both useful and spiritually grounded."
        )),
        (New-Section "Why Navdhya publishes detailed astrology education" @(
          "Navdhya invests in pages like this because families need chart literacy before they can use consultations wisely. Better understanding helps them ask better questions, interpret advice more responsibly, and connect astrology with meaningful ritual preparation.",
          "This educational approach also strengthens trust. People are more comfortable using spiritual guidance when the ideas behind it are explained clearly and respectfully.",
          "That is why Navdhya treats astrology education as part of a long-term spiritual support system, not just as an informational side topic."
        ))
      )
    }
    "astrology-chart" {
      $sections = @(
        (New-Section "Why $keyword is central to personal chart understanding" @(
          "$title matters because chart interpretation is where Jyotish becomes personal. Navdhya explains the topic in a way that helps readers move from vague horoscope language toward the actual structure of kundali-based interpretation. $summary",
          "A family often encounters this subject when trying to understand compatibility, timing, naming, or the basic shape of one person's life path.",
          "That is why Navdhya treats chart-related content as highly practical, not merely technical."
        )),
        (New-Section "Which chart elements readers usually need first" @(
          "Most people do not need every technical detail at once. Navdhya starts with the most useful chart anchors such as lagna, moon sign, graha placement, houses, and timing context so the reader is not overwhelmed.",
          "These anchors help explain why the same chart can be read in layers rather than through one single label or one isolated placement.",
          "A better grasp of the foundation makes deeper consultation much more productive."
        )),
        (New-Section "How birth details shape interpretation" @(
          "$keyword is strongly affected by details such as $secondaryPhrase. Navdhya explains this carefully because even small differences in chart data can change house position, lagna, and the emphasis of interpretation.",
          "That is why chart accuracy matters so much in Vedic astrology. The more exact the starting data, the more stable the interpretive framework becomes.",
          "This is also where families often realize why a serious chart reading cannot be reduced to a generic zodiac summary."
        )),
        (New-Section "Where chart understanding affects family decisions" @(
          "Navdhya often sees readers searching this topic before marriage discussions, naming ceremonies, muhurta selection, or a major personal transition. In each case, the chart is being used as a guide for timing and self-understanding.",
          "That practical role helps explain why chart literacy matters even for people who never plan to study astrology deeply.",
          "A reader who understands the basic chart language can engage advice more thoughtfully and less passively."
        )),
        (New-Section "What readers often get wrong about kundali interpretation" @(
          "A common mistake is to assume that one planet or one house alone defines the whole chart. Navdhya explains that chart reading is relational: placements, aspects, periods, strengths, and timing all influence the final interpretation.",
          "Another mistake is to treat the chart as a rigid sentence rather than as a map of tendencies and periods requiring judgment.",
          "Good explanation helps the reader hold both seriousness and balance at the same time."
        )),
        (New-Section "Why Navdhya teaches chart basics this way" @(
          "Navdhya publishes detailed chart guides so families can approach consultations more confidently and understand how ritual timing, naming choices, or compatibility conversations are actually being evaluated.",
          "That stronger base of understanding improves not only search visibility, but also the quality of the family's eventual spiritual guidance journey.",
          "When readers understand charts better, they can use astrology more responsibly and more meaningfully."
        ))
      )
    }
    "astrology-dosha" {
      $sections = @(
        (New-Section "Why $keyword attracts so much attention in astrology" @(
          "$title is a high-interest topic because dosha-related language often reaches families during stressful or emotionally charged moments. Navdhya explains the subject carefully so readers understand what the term means without slipping into fear-based interpretation. $summary",
          "That balanced approach matters because dosha language can easily be exaggerated in informal astrology discussions.",
          "Navdhya therefore frames $keyword as a traditional interpretive category that must be understood in context."
        )),
        (New-Section "What astrologers usually mean by this condition" @(
          "Navdhya explains $keyword by looking at the chart logic behind it rather than repeating only popular summaries. This helps readers understand how the condition is traditionally identified and why it is considered significant.",
          "Most dosha discussions also involve related ideas such as $secondaryPhrase, so the page explains how those associations are typically understood.",
          "That context makes the subject easier to approach calmly and more accurately."
        )),
        (New-Section "How families feel its impact in real life" @(
          "People usually search $keyword because they want to understand recurring obstacles around marriage, family harmony, finances, emotional pressure, or ritual timing. Navdhya acknowledges that lived concern directly instead of pretending the topic is purely academic.",
          "At the same time, the page avoids overclaiming certainty. Astrology can describe patterns and periods, but it should not be used to produce panic.",
          "A grounded explanation helps the reader move from anxiety toward informed reflection."
        )),
        (New-Section "How remedies are traditionally approached" @(
          "Navdhya explains that remedies linked to $keyword usually combine spiritual discipline, mantra, puja, charity, observance, and sometimes lifestyle restraint rather than one dramatic solution.",
          "This matters because people often search for a single cure when tradition usually offers a more integrated response.",
          "Understanding that integrated remedial mindset helps families approach puja and guidance with more maturity."
        )),
        (New-Section "What readers should avoid when interpreting doshas" @(
          "A frequent mistake is to assume that every dosha guarantees a negative outcome. Navdhya explains that strength, cancellation, timing, house context, and the full chart all affect how any affliction should be interpreted.",
          "Another mistake is to accept alarming advice without understanding the logic behind it. Good astrology guidance should increase clarity, not dependence on fear.",
          "This is one reason serious educational pages remain so valuable in this area."
        )),
        (New-Section "Why Navdhya covers dosha topics in depth" @(
          "Families search these topics because they want clarity before making decisions about marriage, remedies, pujas, or consultations. Navdhya sees it as important to provide explanations that are traditional, balanced, and practically useful.",
          "That approach helps readers decide whether they need chart review, ritual support, or simply better understanding before taking the next step.",
          "In that sense, strong dosha content helps protect both ritual seriousness and family confidence."
        ))
      )
    }
    "astrology-lifestyle" {
      $sections = @(
        (New-Section "Why $keyword matters beyond prediction alone" @(
          "$title matters because many people approach spiritual guidance not only for prediction but for orientation, timing, and a steadier way to think about life choices. Navdhya explains the subject in that broader spiritual-lifestyle frame. $summary",
          "This is especially important for readers who are trying to connect astrology with wellbeing, family planning, personal discipline, or devotional practice.",
          "Navdhya therefore treats the topic as practical spiritual guidance rather than entertainment."
        )),
        (New-Section "How the topic connects to daily life and inner stability" @(
          "Subjects like $keyword often influence how people think about work, relationships, habits, retreat, meditation, or a sense of readiness for change. Navdhya explains those connections clearly so the page feels relevant to real life.",
          "The point is not to make every life choice dependent on astrology. It is to show where astrology and spiritual reflection can meaningfully support one another.",
          "That balance helps the page stay useful without becoming exaggerated."
        )),
        (New-Section "Which traditional ideas usually shape the guidance" @(
          "Navdhya explains $keyword in relation to frameworks such as $secondaryPhrase, along with graha influence, timing, devotional discipline, and the broader logic of dharmic decision-making.",
          "This helps readers see that spiritual guidance usually draws from an ecosystem of ideas rather than one isolated belief.",
          "The result is a more realistic picture of how astrology enters daily Hindu life."
        )),
        (New-Section "How families use this guidance responsibly" @(
          "A responsible use of $keyword usually means taking it as one source of reflective guidance while still honoring ethics, communication, practical judgment, and family responsibility. Navdhya emphasizes that balance throughout its educational content.",
          "Readers are often most helped when astrology becomes a tool for better timing and self-awareness rather than a source of dependency.",
          "That is the spirit in which Navdhya presents these subjects."
        )),
        (New-Section "What the topic can and cannot realistically offer" @(
          "Navdhya is careful to explain the limits of spiritual guidance as well as its strengths. Astrology, numerology, meditation support, or allied guidance may help people notice patterns and choose calmer timings, but they do not replace accountability or qualified professional care where needed.",
          "Being clear about those limits makes the guidance more trustworthy, not less.",
          "This kind of honesty is central to how Navdhya wants spiritual support to be understood."
        )),
        (New-Section "Why Navdhya writes about spiritual guidance this way" @(
          "Navdhya publishes lifestyle-oriented astrology content because readers increasingly want help applying tradition to contemporary life without losing nuance. A strong page should help them understand both the promise and the limits of the guidance.",
          "That makes the content more useful in search, but more importantly it makes the content safer and more grounded for families to use.",
          "The aim is always clarity with reverence, not vague inspiration detached from practice."
        ))
      )
    }
    "ritual-prep-guide" {
      $sections = @(
        (New-Section "Why $keyword matters before the ritual begins" @(
          "$title matters because ritual quality is often decided long before the first mantra is spoken. Navdhya explains preparation topics carefully so households can approach puja with more order, confidence, and cleanliness. $summary",
          "Preparation guidance is especially valuable for urban families who want to do things properly but cannot depend on inherited daily instruction.",
          "That is why Navdhya treats preparation as part of the ritual itself rather than a minor logistical step."
        )),
        (New-Section "What households usually need to understand first" @(
          "Before preparing for $keyword, families usually need clarity around purity, sequence, placement, and the role of core items such as $secondaryPhrase. Navdhya makes these fundamentals explicit so the page is genuinely usable.",
          "This foundation matters because many preparation mistakes happen before the family even realizes something is missing or misplaced.",
          "A better starting framework usually leads to a calmer ritual environment."
        )),
        (New-Section "How the preparation supports the actual ceremony" @(
          "Preparation is not separate from worship. Navdhya explains how $keyword influences the flow of the ceremony, the readiness of materials, the comfort of participants, and the devotional atmosphere of the home.",
          "When the preparation is strong, the ritual often feels more coherent from beginning to end.",
          "That practical benefit is one reason ritual-prep topics deserve long-form explanation."
        )),
        (New-Section "Common mistakes families make while preparing" @(
          "The most common problems are usually simple ones: unclear placement, unclean vessels, rushed sourcing, incorrect storage, or a lack of role clarity among family members. Navdhya addresses these because they are frequent and avoidable.",
          "A good preparation guide helps the household avoid stress before it starts.",
          "It also helps preserve the devotional mood by reducing last-minute scrambling."
        )),
        (New-Section "How modern homes adapt while keeping the core intact" @(
          "Navdhya writes preparation guides for real apartments, compact puja corners, family kitchens, and busy weekday schedules. The point is not to make the ritual casual, but to explain what is essential and how it can be arranged properly.",
          "That balance between realism and reverence is one reason these pages are useful to modern households.",
          "It helps families keep practice alive even when conditions are less than ideal."
        )),
        (New-Section "Why Navdhya invests in preparation-focused education" @(
          "Families search for $keyword because they want to avoid uncertainty and honor the ritual well. Navdhya treats that intent seriously by turning preparation topics into structured, dependable guidance.",
          "This improves not only search performance but also actual household readiness on the ceremony day.",
          "That is why preparation content remains a major part of the Navdhya editorial system."
        ))
      )
    }
    "ritual-prep-materials" {
      $sections = @(
        (New-Section "Why $keyword matters in ritual preparation" @(
          "$title matters because ritual materials are never only decorative. Navdhya explains them in terms of function, symbolism, quality, and household readiness so readers understand why each item matters. $summary",
          "A better understanding of materials helps the family prepare with more confidence and less waste.",
          "It also makes the actual ceremony easier to follow once it begins.",
          "Navdhya sees this kind of material literacy as one of the simplest ways to make home worship feel less stressful, more intentional, and easier to repeat well."
        )),
        (New-Section "What the item is and why it is used" @(
          "Navdhya explains $keyword by showing how it is traditionally used, what role it plays, and how it is often connected to related items such as $secondaryPhrase.",
          "This practical explanation matters because many households know the object visually but not ritually.",
          "Once the role is clearer, the material becomes easier to source and use correctly."
        )),
        (New-Section "How use can vary by deity, ceremony, or family custom" @(
          "Material usage is rarely identical across every puja. Navdhya highlights those variations so families understand why one item may be central in one setting and optional in another.",
          "This helps readers avoid assuming that every list is universal.",
          "It also encourages more thoughtful preparation instead of rote copying."
        )),
        (New-Section "What quality and handling considerations matter" @(
          "The value of $keyword is not only in having it, but in how it is prepared, stored, cleaned, and presented. Navdhya explains these practical details because they strongly affect ceremony readiness.",
          "Careful handling also protects the symbolic dignity of the item itself.",
          "That is especially important for households trying to build a stable puja routine at home."
        )),
        (New-Section "How families make mistakes with ritual materials" @(
          "Common mistakes include buying inappropriate substitutes, storing sacred items carelessly, forgetting cleanliness rules, or not understanding how the material enters the sequence of worship. Navdhya addresses these directly because they are so common.",
          "A stronger material guide reduces those errors and makes the ritual easier to perform calmly.",
          "That practical value is one reason ritual-preparation content performs so strongly."
        )),
        (New-Section "Why Navdhya keeps expanding material guides" @(
          "Families search for $keyword because preparation questions often feel small until they affect the ritual itself. Navdhya responds by turning those practical questions into full educational resources.",
          "That approach helps households source better, organize better, and participate more confidently once the puja begins.",
          "In that sense, material literacy is part of ritual literacy, and Navdhya treats it accordingly."
        ))
      )
    }
    "regional-tradition" {
      $sections = @(
        (New-Section "Why $keyword matters within India's ritual diversity" @(
          "$title matters because Hindu ritual life has never been uniform across the subcontinent. Navdhya explains regional traditions by showing how geography, language, temple culture, priestly lineages, and local devotional memory shape practice. $summary",
          "This matters for families because what feels normal in one region can look quite different in another while still belonging to recognizable Hindu tradition.",
          "Navdhya therefore treats regional explanation as a way to strengthen both understanding and ritual confidence."
        )),
        (New-Section "What gives this regional tradition its distinctive character" @(
          "$keyword is often associated with $secondaryPhrase. Navdhya explains these elements not as isolated curiosities, but as part of a broader regional ritual grammar built over time.",
          "That grammar may include specific festival emphases, distinctive puja styles, household observances, local temple rhythms, or community-preferred ceremonial forms.",
          "A clearer view of those patterns helps readers understand why regional customs remain so emotionally important."
        )),
        (New-Section "How family, temple, and local community shape the practice" @(
          "Regional traditions are usually sustained through a combination of household habit, temple-centered observance, and community repetition. Navdhya explains this because a regional ritual culture is rarely preserved by texts alone.",
          "It survives through songs, food, timing, processions, local deities, seasonal observances, and the practical knowledge carried by elders and priests.",
          "That living continuity is one reason regional ritual identity remains so strong across generations."
        )),
        (New-Section "Where the tradition differs from other Hindu regions" @(
          "Navdhya highlights comparative differences carefully so readers can see what is structurally distinctive without turning regional identity into a hierarchy. The point is to clarify difference, not rank it.",
          "One region may emphasize temple liturgy, another household vrata, another public festival processions, and another localized deity traditions.",
          "These differences help explain why ceremony planning often requires regional sensitivity rather than one-size-fits-all assumptions."
        )),
        (New-Section "Why regional awareness matters for modern families" @(
          "Many urban households now live far from their ancestral region, which makes explanation more important than ever. Navdhya writes these guides so families can preserve regional identity even when the surrounding social environment has changed.",
          "This is especially relevant for weddings, samskaras, annual festivals, and temple-linked observances where family expectations may be deeply regional.",
          "A better explained tradition is easier to carry forward with confidence."
        )),
        (New-Section "How Navdhya approaches regional ritual authenticity" @(
          "Navdhya treats regional tradition as a real part of ritual authenticity, not a decorative add-on. Families often need ceremonies that feel recognizably their own, and that requires attention to language, sequence, priest fit, and inherited custom.",
          "That is why pages like this connect cultural explanation to practical ritual planning rather than leaving the topic at the level of folklore.",
          "The stronger the regional understanding, the easier it becomes to preserve continuity without confusion."
        ))
      )
    }
    "platform-tech" {
      $sections = @(
        (New-Section "Why $keyword matters in a ritual services platform" @(
          "$title matters because spiritual services only benefit from technology when the system reduces confusion without flattening the sacred context of the ritual. Navdhya explains the platform in that practical way. $summary",
          "This means the technology has to support trust, clarity, and coordination rather than behaving like a generic commerce tool.",
          "Navdhya therefore frames platform technology as service infrastructure around ritual life, not as an end in itself."
        )),
        (New-Section "Which operational problem the platform is solving" @(
          "Navdhya explains $keyword by connecting it to real service problems such as priest matching, booking clarity, customer communication, scheduling logic, and post-ceremony follow-through.",
          "That operational framing matters because readers usually care less about abstract tech language than about whether the system actually improves the family experience.",
          "A useful platform explanation therefore begins with the ritual coordination problem, not with the feature list."
        )),
        (New-Section "How digital systems support trust and consistency" @(
          "$keyword often sits alongside concerns such as $secondaryPhrase. Navdhya explains how those digital layers help create a more consistent service journey for families, pandits, and internal coordination teams.",
          "This is especially important in ritual services, where communication gaps or expectation mismatches can affect the ceremony itself.",
          "Technology becomes meaningful here when it improves reliability rather than adding noise."
        )),
        (New-Section "Where human judgment still remains essential" @(
          "Navdhya does not describe its platform as a replacement for priest expertise, family preference, or service judgment. The system is there to organize information, support matching, and reduce operational friction.",
          "That distinction matters because rituals are contextual. Human interpretation and communication remain central even when the supporting systems become more sophisticated.",
          "A strong platform therefore balances automation with informed human intervention."
        )),
        (New-Section "Why the platform matters to modern users" @(
          "Modern users expect the booking journey to be clear, responsive, and transparent, even when the underlying service is deeply traditional. Navdhya writes platform pages so readers can understand how those expectations are met without sacrificing ritual seriousness.",
          "This is particularly relevant for online participation, mobile access, global users, repeat bookings, and time-sensitive ceremonies.",
          "A better explained platform usually feels more trustworthy before the user ever submits a request."
        )),
        (New-Section "How Navdhya uses platform explanation strategically" @(
          "Navdhya treats platform-technology content as both educational and trust-building. Families want to know how booking works, how support is coordinated, and how service quality is maintained beyond the marketing layer.",
          "That is why the platform is explained in concrete operational terms rather than as empty innovation language.",
          "The goal is to make the digital side of spiritual service legible, credible, and easier to evaluate."
        ))
      )
    }
    default {
      $sections = @(
        (New-Section "Why $keyword matters in living Hindu practice" @(
          "$title is an important topic because it connects directly to how families understand ceremony, worship, and sacred continuity. Navdhya explains the subject in a practical and respectful way so the page supports real understanding. $summary",
          "Readers often know the term but not the deeper structure around it. A strong guide helps close that gap.",
          "That is why Navdhya treats educational pages as part of active spiritual support."
        )),
        (New-Section "How families usually encounter this topic" @(
          "Most people encounter $keyword while planning a ritual, observing a family custom, or trying to understand why a familiar step is performed. Navdhya writes from that real-life context instead of assuming academic distance.",
          "This practical framing helps the page stay useful for beginners without becoming shallow.",
          "It also makes the subject much easier to connect to everyday ritual life."
        )),
        (New-Section "The main ideas, actions, and meanings involved" @(
          "$keyword is often associated with $secondaryPhrase. Navdhya explains how these elements fit together so the page becomes more than a surface definition.",
          "Meaning in Hindu ritual is usually carried through sequence, symbolism, spoken recitation, and family participation.",
          "A clear explanation therefore helps the reader appreciate both the action and the intention behind it."
        )),
        (New-Section "How modern families keep the topic relevant" @(
          "Navdhya often sees households trying to preserve tradition in settings very different from older domestic patterns. Clear explanation helps them adapt without losing the core.",
          "This is why educational content still matters. It supports continuity under modern conditions.",
          "A better understood ritual topic is much easier to preserve with dignity."
        )),
        (New-Section "Why good explanation improves participation" @(
          "When the meaning of $keyword is clearer, family participation usually becomes calmer and more confident. Navdhya sees this repeatedly across ceremony and puja planning journeys.",
          "Explanation therefore supports practice. It does not sit outside practice.",
          "That practical benefit is one of the main reasons Navdhya keeps building long-form ritual guidance."
        )),
        (New-Section "How Navdhya approaches the subject" @(
          "Navdhya aims to explain the subject with enough depth for serious readers and enough clarity for first-time learners. That balance matters if ritual education is going to stay usable.",
          "The goal is not merely to define the topic, but to help the reader recognize its place in living tradition.",
          "That is why pages like this remain a permanent part of the Navdhya content system."
        ))
      )
    }
  }

  return Render-Sections @($sections + (Get-ExtendedSections $Entry))
}

function New-HowToData {
  param($Entry)

  $keyword = $Entry.primaryKeyword
  $summary = $Entry.summary
  $secondaryPhrase = Get-SecondaryPhrase $Entry

  switch ($Entry.contentFamily) {
    "household-howto" {
      $sections = @(
        (New-Section "Why families need a usable $keyword routine" @(
          "Families usually search for a clear $keyword guide because household rituals work only when they are practical enough to repeat. Navdhya writes these pages to help families establish a stable home practice without losing devotional seriousness. $summary",
          "That means focusing on sequence, readiness, and the small practical decisions that make a home observance sustainable.",
          "A good household guide should therefore feel simple enough to use and complete enough to trust.",
          "This is especially important for homes balancing devotion with work, school, and limited time."
        )),
        (New-Section "What to prepare before beginning" @(
          "$keyword becomes easier when the home space, offerings, and family roles are prepared in advance. Navdhya emphasizes this because confusion usually starts before the ritual itself, not during the prayer.",
          "The materials often include items linked with $secondaryPhrase, along with a clean altar or puja corner and a settled sense of the ritual objective.",
          "Preparation supports calm, and calm supports devotion.",
          "Even a small amount of order can change how confidently the family participates."
        )),
        (New-Section "How the home sequence usually unfolds" @(
          "Most forms of $keyword move through a compact household pattern of invocation, sankalpa, the main offering or observance, and respectful completion. Navdhya explains this flow so the practice can be repeated consistently.",
          "This is especially important for busy homes where the ritual must remain meaningful without becoming unmanageably long.",
          "A clearer structure usually leads to better participation by everyone in the household.",
          "It also helps younger family members learn the rhythm of the observance more naturally."
        )),
        (New-Section "How to adapt the practice for daily life" @(
          "Navdhya writes these guides for real homes, workday schedules, and mixed-generation families. The point is not to make the ritual casual, but to make it genuinely workable.",
          "That means clarifying the essential sequence and helping the household avoid overcomplicating a practice that should build steadiness over time.",
          "A repeatable practice almost always serves the family better than an elaborate routine that cannot be sustained.",
          "Consistency matters more than drama in long-term household ritual life."
        )),
        (New-Section "Why Navdhya treats household ritual guidance seriously" @(
          "Household ritual is where most people actually live their spiritual life. Navdhya therefore treats pages like this as practical religious infrastructure, not filler content.",
          "The stronger the household routine becomes, the easier it is for tradition to remain active across months and years.",
          "That is the long-term value of a clear home-ritual guide.",
          "It turns scattered intention into a rhythm the family can actually keep."
        ))
      )
    }
    "spiritual-howto" {
      $sections = @(
        (New-Section "Why people need a practical $keyword framework" @(
          "Readers usually search for a clear $keyword guide because spiritual intentions often fail when the practice is too vague to repeat. Navdhya writes these pages to turn aspiration into a stable method. $summary",
          "This matters because spiritual discipline is rarely built by inspiration alone. It usually depends on a clear structure that can survive ordinary life.",
          "A good guide therefore has to be both reflective and practical.",
          "That balance is what allows a practice to deepen instead of fading quickly."
        )),
        (New-Section "How to prepare mind, body, and setting" @(
          "$keyword is easier when posture, breath, timing, and the practice setting are chosen deliberately. Navdhya emphasizes this preparation stage because spiritual steadiness usually begins before the main exercise starts.",
          "The reader may also need simple supports linked with $secondaryPhrase depending on the exact practice.",
          "That small amount of preparation often makes the whole routine feel more coherent.",
          "It also reduces the mental friction that keeps many practices from becoming regular."
        )),
        (New-Section "How the core practice should be approached" @(
          "Most forms of $keyword are strongest when they follow a simple but repeatable pattern. Navdhya explains the sequence so readers can understand what should happen first, what should hold the center, and how the practice should close.",
          "That structure is especially important for beginners who want sincerity without confusion.",
          "Clarity usually produces better consistency than intensity.",
          "A smaller practice done well often produces more benefit than a larger one done rarely."
        )),
        (New-Section "How to make the practice sustainable" @(
          "Navdhya writes these guides for modern schedules, limited attention, and real household life. The aim is not to create a heroic one-day routine, but a spiritually useful practice that can actually continue.",
          "This is why pacing, simplicity, and realistic repetition matter so much.",
          "A small stable practice generally deepens faster than a demanding routine that gets abandoned.",
          "That is the practical logic behind nearly every durable spiritual habit."
        )),
        (New-Section "Why Navdhya publishes modern spiritual method guides" @(
          "Pages like this help readers connect Hindu spiritual language to everyday practice in a way that is disciplined but not obscure. Navdhya treats that bridge as important because many modern seekers need structure more than inspiration.",
          "A better method usually leads to better continuity, stronger reflection, and more grounded spiritual growth.",
          "That is why practical spiritual guides remain central to the Navdhya knowledge base.",
          "They make inner practice easier to begin, understand, and sustain."
        ))
      )
    }
    "platform-tech-howto" {
      $sections = @(
        (New-Section "Why families need a clear $keyword process" @(
          "Families usually search for a usable $keyword guide because platform workflows help only when the ritual requirement is described clearly enough for accurate coordination. Navdhya writes this guide as operational ritual support rather than as form-filling advice. $summary",
          "A strong brief reduces mismatch between family expectation, pandit preparation, and ceremony-day execution.",
          "That is why Navdhya treats the briefing step as part of service quality.",
          "The more clearly the ritual is described at the start, the easier it becomes for every later stage of the booking to stay aligned."
        )),
        (New-Section "What to gather before writing the request" @(
          "The brief becomes stronger when the family first confirms the ceremony type, intended date, locality, participant details, and whether the observance follows a regional or sampradaya-specific format.",
          "Navdhya highlights this preparation because vague requests usually slow matching and create avoidable follow-up questions.",
          "The clearer the inputs, the smoother the service coordination usually becomes.",
          "This preparation stage also helps the family notice any missing decisions before the request reaches the operations team."
        )),
        (New-Section "How the brief should be structured" @(
          "A useful $keyword document usually moves from ritual objective to household context, then to logistics, then to any special preparation needs. Navdhya recommends that structure because it mirrors how the service team and pandit evaluate readiness.",
          "This approach also helps the family separate essential ritual information from optional background detail.",
          "Order matters because operational clarity depends on readable sequencing.",
          "When the information is structured well, the brief becomes easier to review, easier to assign, and easier to act on."
        )),
        (New-Section "Which mistakes reduce briefing quality" @(
          "Common problems include naming the ceremony too broadly, skipping regional preferences, forgetting time constraints, or assuming the pandit will infer material or participant details later.",
          "Navdhya addresses those gaps directly because they are among the fastest ways to create service friction.",
          "A stronger brief usually prevents clarification loops before they start.",
          "It also reduces the chance that the family and the assigned pandit arrive with different assumptions about the same ceremony."
        )),
        (New-Section "Why Navdhya uses ritual briefs as coordination tools" @(
          "Navdhya treats ritual briefs as working documents that align booking, priest assignment, material readiness, and family expectations around one shared view of the ceremony.",
          "That makes the digital process more trustworthy because the user can see how their information improves the real-world ritual outcome.",
          "In that sense, better briefing is not administrative overhead. It is part of better ritual delivery."
        )),
        (New-Section "How a stronger brief improves the ceremony day itself" @(
          "The quality of the ceremony day often depends on information gathered days earlier. Navdhya therefore connects better briefing directly to better readiness on the ground.",
          "When the pandit already understands the family context, regional preference, and logistical constraints, less time is wasted clarifying basics after arrival.",
          "That improvement matters because a calmer beginning usually leads to a more focused and more respectful ritual experience."
        ))
      )
    }
    "astrology-remedy-howto" {
      $sections = @(
        (New-Section "Why families look for a clear $keyword process" @(
          "Families usually search for a strong $keyword guide when they are trying to respond to a dosha, a difficult planetary period, or repeated uncertainty about what remedy is traditionally recommended. Navdhya explains these puja methods with both ritual clarity and astrological context. $summary",
          "This matters because remedy pujas are often approached with anxiety. A calmer, clearer method helps the family understand what is actually being done and why.",
          "Navdhya therefore treats astrology-linked how-to pages as ritual education first, not fear-driven content."
        )),
        (New-Section "How to prepare the chart context and the ritual setup" @(
          "A remedy guide is easier to follow when the family understands both the ritual sequence and the astrological concern behind it. Navdhya therefore explains $keyword alongside the basic chart logic, the devotional intention, and the materials linked with $secondaryPhrase.",
          "That combined preparation helps the family avoid treating the puja as an isolated technical fix.",
          "It also helps them approach the observance with more seriousness and less confusion."
        )),
        (New-Section "How the remedy sequence usually unfolds" @(
          "Most astrology remedies move through preparation, sankalpa, graha or deity invocation, mantra or offering sequence, and respectful completion. Navdhya explains that order so the family can see how the ritual addresses the concern in a disciplined way.",
          "Once the structure is clear, the observance becomes much easier to perform or follow with a priest.",
          "That clarity is especially valuable when the family is already emotionally invested in the outcome."
        )),
        (New-Section "Common mistakes in graha-related remedies" @(
          "The most common mistakes are treating the remedy as guaranteed, skipping the devotional logic, using incomplete materials, or following a frightening summary without understanding the actual puja structure. Navdhya addresses these directly because they are widespread.",
          "A better guide reduces fear and improves ritual seriousness at the same time.",
          "That is one reason detailed remedial how-to content remains so important."
        )),
        (New-Section "When self-guided practice is enough and when priest support helps" @(
          "Some graha-related practices are suitable for disciplined household observance, while fuller pujas may benefit from priest guidance, especially where mantra sequence, havan, or specialized procedure is important. Navdhya helps families understand that distinction.",
          "This makes the page more useful because it shows how educational understanding and service support can work together.",
          "It also prevents families from underpreparing for a more formal remedial rite."
        )),
        (New-Section "Why Navdhya explains remedial pujas in this way" @(
          "Navdhya writes remedy guides so families can approach graha-related observances with composure, realism, and better ritual understanding. The goal is not only to increase discoverability, but to reduce misinformation around sensitive astrology topics.",
          "When the ritual logic is clearer, the family can choose the right next step with more confidence.",
          "That is the real value of a strong astrology remedy guide."
        ))
      )
    }
    "festival-howto" {
      $sections = @(
        (New-Section "Why families need a clear $keyword festival sequence" @(
          "Families usually search for a strong $keyword guide because festival observances often stretch across several steps, several watches of the day, or even several days. Navdhya explains the process with a practical festival rhythm in mind so the household can follow it more confidently. $summary",
          "This matters because festival worship combines devotion, preparation, timing, and family participation in a way that is easy to dilute if the order is unclear.",
          "Navdhya therefore treats festival how-to pages as both ritual instruction and seasonal household planning."
        )),
        (New-Section "What to prepare before the observance begins" @(
          "$keyword becomes easier when fasting rules, altar readiness, offerings, lamps, and family roles are settled in advance. Navdhya highlights this stage because festival observances are often disrupted by small preparation gaps rather than by major ritual errors.",
          "The setup usually includes items connected with $secondaryPhrase, along with a clean space, festival-specific food or prasad planning, and a clear sense of the day's devotional center.",
          "A well-prepared beginning helps the festival feel intentional rather than rushed."
        )),
        (New-Section "How the festival sequence usually unfolds" @(
          "Most forms of $keyword follow a recognizable festival pattern of preparation, sankalpa, core puja or observance, aarti or offering, and meaningful completion. Navdhya explains that flow so the family can see how one stage of the day leads into the next.",
          "This is especially useful for observances that include fasting, moon or sunrise timing, day-by-day variation, or evening household rituals.",
          "A clearer sequence makes the observance easier to repeat every year with less uncertainty."
        )),
        (New-Section "How fasting, timing, and symbolism fit together" @(
          "Festival rituals are often shaped by time-sensitive acts, dietary discipline, and symbolic offerings. Navdhya explains how those parts work together so the observance does not feel like a collection of disconnected customs.",
          "This matters because festival worship often reaches its deepest meaning when households understand not only what to do, but why the order and symbolism matter.",
          "That deeper clarity helps the ritual remain devotional instead of purely performative."
        )),
        (New-Section "Common mistakes households make during festival pujas" @(
          "Readers often struggle with late preparation, unclear fasting rules, missing items, or uncertainty about what must happen at a specific time. Navdhya addresses these issues directly because they are common across many seasonal observances.",
          "A stronger guide helps the family protect the emotional tone of the festival while still adapting to modern schedules.",
          "That kind of clarity is often what makes an annual observance sustainable over the long term."
        )),
        (New-Section "Why Navdhya publishes detailed seasonal guides" @(
          "Festival pages matter because families usually return to them year after year. Navdhya writes $keyword guides to function as repeat-use ritual references rather than one-time informational pages.",
          "That helps preserve continuity, improves search discoverability, and makes the seasonal observance easier to teach within the household.",
          "In that sense, a strong festival how-to page becomes part of the family ritual calendar itself."
        ))
      )
    }
    "ritual-prep-howto" {
      $sections = @(
        (New-Section "Why families need a clear $keyword preparation method" @(
          "Families search for a strong $keyword guide because preparation tasks often look simple until they are done incorrectly on an important puja day. Navdhya writes these pages so households can prepare with order, cleanliness, and confidence. $summary",
          "Preparation-focused how-to pages are valuable because they turn vague cultural familiarity into usable ritual readiness.",
          "That is why Navdhya treats them as a core part of household ritual support."
        )),
        (New-Section "What to gather and understand before starting" @(
          "$keyword becomes easier when the family knows the purpose of the task, the placement rules, and the items linked with $secondaryPhrase before beginning. Navdhya emphasizes this preparation stage because it prevents the most common mistakes.",
          "A little clarity before starting usually saves a great deal of ceremony-day stress.",
          "This is especially helpful in homes where not every family member already knows the preparation sequence."
        )),
        (New-Section "How the process usually unfolds step by step" @(
          "Most forms of $keyword move through a practical sequence of cleaning, arrangement, activation or offering, and final placement or completion. Navdhya explains that flow so the household can follow it calmly instead of improvising.",
          "Once the order is clear, the task becomes far easier to repeat for future observances.",
          "That repeatability is one of the main strengths of a good ritual-prep guide."
        )),
        (New-Section "Common preparation mistakes families make" @(
          "Readers often struggle with incorrect placement, missing support items, rushed setup, or uncertainty about what is symbolically essential. Navdhya calls out these issues because they happen far more often than dramatic ritual mistakes.",
          "A stronger guide makes the preparation feel manageable and protects the atmosphere of the puja itself.",
          "That practical clarity is often what separates a calm ceremony from a stressful one."
        )),
        (New-Section "How to adapt the method for modern homes" @(
          "Navdhya writes $keyword guides for apartments, shared family homes, and limited-space altars rather than idealized ceremonial settings. The goal is to preserve the ritual logic while still helping the method work in daily life.",
          "This realistic approach helps families continue the practice instead of abandoning it because the setup feels intimidating.",
          "A method that works under real household conditions is always more valuable than a perfect but unusable one."
        )),
        (New-Section "Why Navdhya keeps publishing practical setup guides" @(
          "Preparation questions are some of the most common questions households have before a puja. Navdhya publishes step-by-step guides like this so people can prepare correctly, ask better questions, and approach the ritual with less uncertainty.",
          "That makes the page useful not only as search content but as a real preparation tool for the home.",
          "In that sense, setup guidance is one of the most practical forms of ritual education Navdhya provides."
        ))
      )
    }
    default {
      $sections = @(
        (New-Section "Why families need a clear $keyword process" @(
          "Families usually search for a strong $keyword guide because they want a sequence they can actually follow with confidence at home or during a planned ceremony. Navdhya writes these pages with a practical, step-based approach. $summary",
          "A how-to page is most useful when it explains both the devotional purpose and the procedural order. Navdhya therefore treats ritual clarity as part of ritual quality.",
          "That makes the guide easier to use for first-time worshippers and easier to repeat for families building long-term habits."
        )),
        (New-Section "What to prepare before you begin" @(
          "$keyword is easier when the space, materials, and family roles are prepared in advance. Navdhya highlights this stage because preparation removes the most common sources of confusion once the ritual starts.",
          "The setup often includes items connected with $secondaryPhrase, along with a clean altar, lamp arrangement, water, and any text or mantras needed for recitation.",
          "Good preparation does not make the ritual mechanical. It helps the devotional atmosphere feel calmer and more complete."
        )),
        (New-Section "How the main sequence usually unfolds" @(
          "Most forms of $keyword move through a recognizable sequence of preparation, invocation, sankalpa, core offering, closing prayer, and respectful completion. Navdhya explains that flow so readers understand the logic behind each step.",
          "Once the sequence becomes clear, it becomes much easier to involve elders, children, or guests without breaking the rhythm of the observance.",
          "This is one reason how-to pages remain so useful: they turn uncertain intention into ordered participation."
        )),
        (New-Section "Common mistakes and how Navdhya helps avoid them" @(
          "Readers often struggle with missing items, unclear order, or uncertainty about what is essential versus optional. Navdhya addresses these issues directly because they are far more common than dramatic ritual mistakes.",
          "A stronger guide helps the family protect the devotional core while still adapting to household realities like time limits, apartment space, or first-time participation.",
          "That kind of clarity reduces hesitation and supports a more meaningful experience."
        )),
        (New-Section "How to adapt the guide to real household conditions" @(
          "Navdhya writes $keyword guides for actual homes, not idealized textbook settings. That means accounting for smaller altars, working schedules, mixed-generation families, and varying levels of ritual familiarity.",
          "The point is not to dilute the practice. It is to help the family identify the essential sequence and hold it well.",
          "A guide that can survive real life is much more valuable than one that sounds complete but cannot be used."
        )),
        (New-Section "When to perform it yourself and when to seek support" @(
          "Some versions of $keyword are well suited to self-guided household practice, while others benefit from priest support, especially when mantra, muhurta, or a fuller ceremonial structure is important. Navdhya helps families make that distinction more confidently.",
          "This is where educational content and service support meet. A family can learn the basic method and still decide whether a verified pandit would help for a fuller observance.",
          "That is one reason Navdhya sees how-to guidance as part of a larger ritual support system rather than a standalone content format."
        ))
      )
    }
  }

  return [ordered]@{
    sections = if ($Entry.contentFamily -in @("festival-howto","household-howto","spiritual-howto","platform-tech-howto")) { @($sections) } else { @($sections + (Get-ExtendedSections $Entry)) }
    howToSteps = New-HowToSteps $Entry
  }
}

function New-ServiceData {
  param($Entry)

  $title = $Entry.title
  $keyword = $Entry.primaryKeyword
  $summary = $Entry.summary
  $topicText = Get-PrimaryTopicText $Entry
  $secondaryPhrase = Get-SecondaryPhrase $Entry
  $isDelhi = $topicText -match "(?i)\bDelhi\b|\bNCR\b"
  $isOnline = $topicText -match "(?i)online|virtual|remote|live-streamed|app"
  $isEmergency = $topicText -match "(?i)emergency|urgent|same-day|short-notice"

  $coverageText = if ($isDelhi) {
    "For Delhi and NCR families, Navdhya explains the service in terms of location readiness, priest travel coordination, and practical ceremony planning rather than vague location promises."
  } else {
    "Navdhya explains the service in a way that helps families understand ceremony fit, household readiness, and the practical planning needed before booking."
  }

  $deliveryText = if ($isOnline) {
    "Because the service may involve remote or live participation, Navdhya also clarifies technology expectations, family roles during the stream, and how the ritual remains structured even when people are not in one place."
  } elseif ($isEmergency) {
    "Because this type of request may be time-sensitive, Navdhya frames the service around responsive coordination, realistic availability checks, and respectful communication under pressure."
  } else {
    "Navdhya places equal emphasis on priest fit, family preparation, and ceremony structure so the experience feels complete rather than transactional."
  }

  switch ($Entry.contentFamily) {
    "astrology-service" {
      $sections = @(
        (New-Section "Why families book $keyword through Navdhya" @(
          "$title reflects a practical search journey: the family wants astrology guidance that feels organized, serious, and easier to trust than scattered informal referrals. Navdhya explains the service as structured spiritual support rather than vague horoscope entertainment. $summary",
          "A good astrology service page should show not only what can be booked, but how the consultation fits into real family decision-making.",
          "That is why Navdhya presents consultations as part of a broader guidance ecosystem that also includes ritual planning and muhurta support."
        )),
        (New-Section "What the consultation service usually includes" @(
          "Families considering $keyword often want to know whether the session is suited to chart reading, compatibility, timing, remedies, or a broader life-path conversation. Navdhya explains service scope in terms of use case, preparation, and reading format.",
          "This clarity matters because astrology sessions are most useful when the purpose is defined before the consultation begins.",
          "Navdhya therefore helps families approach the booking with more focus and more realistic expectations."
        )),
        (New-Section "How session fit and consultation focus are handled" @(
          "Not every astrology need is the same. Navdhya explains $keyword in terms of chart complexity, family context, timing concerns, and whether the person needs a foundational reading or a more focused discussion around one problem.",
          "This fit assessment is important because a better-scoped consultation almost always produces a more useful outcome.",
          "It also helps the family gather the right details before the session starts."
        )),
        (New-Section "How booking, scheduling, and digital readiness usually work" @(
          "Navdhya encourages families to share the reading objective, relevant birth details, preferred format, and scheduling preferences early so the consultation can be planned with more confidence.",
          $coverageText,
          $deliveryText
        )),
        (New-Section "How families should prepare before the reading" @(
          "A strong $keyword experience depends on clarity before the session. Navdhya recommends preparing accurate birth details where available, narrowing the key questions, and identifying whether the consultation connects to marriage, career, ceremony timing, or general guidance.",
          "This preparation helps the session stay practical and easier to apply afterward.",
          "It also reduces the tendency to drift into vague or unfocused conversation."
        )),
        (New-Section "Why Navdhya emphasizes grounded astrology guidance" @(
          "Families trust astrology more when it is explained without exaggeration. Navdhya therefore presents the service in a way that emphasizes reflection, timing awareness, and practical spiritual guidance rather than fear-based certainty.",
          "That grounded tone improves both the user experience and the long-term credibility of the service.",
          "It is also one of the main reasons people look for structured astrology support through Navdhya."
        ))
      )
    }
    "ritual-prep-service" {
      $sections = @(
        (New-Section "Why families book $keyword through Navdhya" @(
          "$title reflects a preparation-focused search intent. The household wants reliable support for the material side of the ceremony so the ritual day feels calmer and more complete. Navdhya explains the service through that readiness lens. $summary",
          "This matters because material support often affects the entire emotional tone of the ceremony, especially for first-time bookers and busy urban families.",
          "Navdhya therefore treats ritual-preparation services as part of ceremony quality, not merely convenience."
        )),
        (New-Section "What the preparation service usually includes" @(
          "Families considering $keyword usually want to know what can be sourced, how quality is considered, and what part of the home-ritual setup the service helps simplify. Navdhya explains this scope in terms of samagri readiness, practical coordination, and ritual fit.",
          "That clarity makes it easier for the family to understand where the service helps most and what still needs to be arranged at home.",
          "A good preparation service should therefore reduce uncertainty before the ceremony begins."
        )),
        (New-Section "How quality, suitability, and ceremony fit are handled" @(
          "Navdhya explains $keyword in terms of material relevance, ceremony type, and household practicality rather than treating every puja as if it needed the same arrangement. That nuance matters because preparation quality is closely tied to the ritual being performed.",
          "The family benefits most when sourcing and setup expectations are discussed with the actual observance in mind.",
          "That is one reason preparation services and ritual guidance work best together."
        )),
        (New-Section "How booking, delivery, and coordination usually work" @(
          "Navdhya encourages families to share the ceremony type, date, location, and preparation expectations early so support can be assessed more realistically.",
          $coverageText,
          $deliveryText
        )),
        (New-Section "How families should prepare alongside the service" @(
          "Even when a support service is involved, a well-run $keyword experience still depends on family readiness. Navdhya recommends confirming altar space, vessel cleanliness, participant roles, and how the materials will be arranged on the ceremony day.",
          "This prevents the service from being treated as a complete substitute for household preparation.",
          "The best outcomes usually happen when service support and family readiness work together."
        )),
        (New-Section "Why Navdhya treats preparation support as trust work" @(
          "Families often remember ceremony preparation just as strongly as the ceremony itself. Navdhya therefore explains preparation services in terms of trust, predictability, and practical usefulness rather than only delivery language.",
          "That framing is important because material readiness is closely tied to the household's sense of calm on the ritual day.",
          "Better preparation support usually produces a better overall ritual experience, which is exactly why Navdhya keeps investing in this category."
        ))
      )
    }
    "platform-tech-service" {
      $sections = @(
        (New-Section "Why families consider $keyword through Navdhya" @(
          "$title reflects a platform-led service need: the household wants predictable recurring support without restarting the booking journey every time. Navdhya explains the service as operational continuity around ritual life. $summary",
          "This matters because regular puja planning often breaks down on scheduling, coordination, and follow-through rather than devotional intent alone.",
          "Navdhya therefore presents subscription-style support as a trust and consistency tool.",
          "The service is meant to reduce planning fatigue while keeping the ritual itself serious and well prepared."
        )),
        (New-Section "What the recurring service usually covers" @(
          "Families considering $keyword usually want to know whether the plan covers priest assignment, repeat scheduling, reminders, and ceremony-type flexibility. Navdhya explains scope in those practical terms.",
          "That clarity matters because recurring ritual support works best when expectations are defined before the first booking is confirmed.",
          "A strong explanation also helps the family judge whether a subscription fits their actual practice rhythm.",
          "This is especially useful for homes balancing weekly devotion with work calendars, school schedules, and elder participation."
        )),
        (New-Section "How scheduling and priest continuity are handled" @(
          "Navdhya explains the service in terms of recurring date planning, ceremony cadence, and whether the household prefers continuity of priest, broad availability, or a mix of both.",
          "This is important because repeat services create a different expectation from one-time bookings.",
          "The system has to support continuity without becoming rigid.",
          "A recurring model succeeds only when the household can depend on the schedule without feeling trapped by it."
        )),
        (New-Section "How families should prepare before enrolment" @(
          "A good $keyword experience begins with a realistic sense of ritual frequency, preferred puja type, household timing constraints, and whether samagri or reminder support is also needed.",
          "Navdhya recommends thinking through those factors early because subscription services are strongest when they fit lived household patterns.",
          "Better setup decisions usually reduce cancellations and confusion later.",
          "Preparation at the start also makes it easier to scale the plan up or down as the family routine changes."
        )),
        (New-Section "Why Navdhya explains recurring services carefully" @(
          "Navdhya treats recurring puja services as part of long-term ritual discipline rather than as a generic membership product. The point is to help families keep worship consistent, organised, and easier to sustain.",
          "That is why the service is explained through ritual continuity, operational clarity, and household usability instead of promotional language alone.",
          "A clearer service model usually leads to better repeat engagement and a steadier devotional routine."
        )),
        (New-Section "How recurring service design supports household discipline" @(
          "A recurring ritual service works best when the digital system supports the family's devotional habits rather than competing with them. Navdhya therefore ties reminders, history, and scheduling logic back to practice continuity.",
          "This matters because many households do not struggle with intent. They struggle with repetition, preparation, and keeping the calendar organized over time.",
          "By reducing those operational burdens, the service can make regular worship more sustainable without making the household feel dependent on opaque systems."
        )),
        (New-Section "Why this matters for long-term trust in Navdhya" @(
          "Families judge recurring services over months, not minutes. Navdhya therefore explains subscription support in terms of predictability, responsive coordination, and whether the system keeps delivering what was promised after the first booking.",
          "That longer time horizon makes service design, reminder quality, and priest continuity more important than on a one-time ritual page.",
          "A recurring plan becomes credible only when the family can see that the platform is helping them maintain a meaningful rhythm of worship."
        ))
      )
    }
    default {
      $sections = @(
        (New-Section "Why families book $keyword through Navdhya" @(
          "$title speaks to a very practical search intent: the family wants a reliable way to arrange the ceremony without losing ritual depth. Navdhya approaches service pages by explaining not only what can be booked, but why the ritual needs thoughtful coordination. $summary",
          "A service page works best when it helps the family understand the ceremony itself, the likely preparation burden, and how priest guidance fits into the experience.",
          "That is why Navdhya treats booking content as an extension of ritual clarity rather than as a sales page alone."
        )),
        (New-Section "What the service usually includes" @(
          "Families considering $keyword often want to know what is covered, what needs to be arranged at home, and how the actual ritual day will unfold. Navdhya explains the service scope in terms of priest coordination, ceremony flow, and material expectations.",
          "The exact format can vary depending on ceremony type, location, timing, and whether the household needs a simpler observance or a fuller setup linked with $secondaryPhrase.",
          "Navdhya keeps this explanation practical so the family can compare expectations before confirming the booking."
        )),
        (New-Section "How priest matching and ritual fit are handled" @(
          "Not every priest is the right fit for every ceremony. Navdhya therefore explains $keyword in terms of ritual specialisation, language comfort, family preference, and service readiness rather than one-size-fits-all assignment.",
          "This is especially important when the ritual includes a detailed sequence, regional preference, or household questions that need patient explanation.",
          "A better match usually produces a more confident family experience and a more orderly ceremony day."
        )),
        (New-Section "How booking, timing, and confirmation usually work" @(
          "Navdhya encourages families to share the ceremony type, desired date, location, and any household preferences early in the process so availability can be assessed realistically.",
          $coverageText,
          $deliveryText
        )),
        (New-Section "How families should prepare before the ritual day" @(
          "A well-run $keyword experience depends on more than priest arrival. Navdhya recommends that families clarify the ritual objective, confirm the space, organise the essential materials, and understand what part of the sequence they will participate in directly.",
          "This preparation reduces ceremony-day confusion and makes it easier for elders, children, and guests to engage meaningfully instead of feeling lost.",
          "Good service coordination therefore begins before the priest reaches the home or the online session begins."
        )),
        (New-Section "Why Navdhya emphasizes clarity, trust, and repeatability" @(
          "Families usually remember a ceremony not only for its ritual steps but also for how predictable, respectful, and well-guided the experience felt. Navdhya sees that as a core service responsibility.",
          "That is why service pages like this explain the flow, priest role, preparation needs, and family expectations in plain language before a booking is made.",
          "Better informed families usually have better ceremony experiences. That is the deeper service logic behind how Navdhya presents $keyword."
        ))
      )
    }
  }

  $finalSections = if ($Entry.contentFamily -eq "platform-tech-service") { @($sections) } else { @($sections + (Get-ExtendedSections $Entry)) }
  return Render-Sections $finalSections
}

function New-LocalBusinessData {
  param($Entry)

  $title = $Entry.title
  $keyword = $Entry.primaryKeyword
  $summary = $Entry.summary

  $sections = @(
    (New-Section "Why families search locally for $keyword" @(
      "$title reflects a local-intent search journey. Families want a priest or puja service they can actually book in their city without sacrificing trust or ritual quality. Navdhya explains the page from that local planning perspective. $summary",
      "Location matters because ceremony timing, travel practicality, and home logistics all shape the actual experience on the ritual day.",
      "That is why Navdhya treats local service pages as both city-specific and ritual-specific."
    )),
    (New-Section "How Navdhya supports Delhi and NCR requests" @(
      "For Delhi and NCR households, Navdhya focuses on service-zone clarity, ceremony planning, and realistic priest coordination rather than vague promises. The aim is to help the family understand how booking works across local neighbourhood conditions.",
      "This includes thinking about building access, traffic windows, ceremony duration, and whether materials or family-preparation support are part of the requirement.",
      "A good local page should therefore make the search journey easier before the family even submits a request."
    )),
    (New-Section "Which ceremonies and formats can usually be arranged" @(
      "$keyword may involve different ceremony sizes, home settings, and priest requirements. Navdhya explains the range in terms of household pujas, major samskaras, recurring rituals, or event-linked observances depending on the family need.",
      "This helps the household understand whether it needs a simple priest visit, a fuller ceremony setup, or a more specialized pandit with specific experience.",
      "That kind of upfront clarity is particularly useful when families are booking for elders or managing an event timeline."
    )),
    (New-Section "How local coordination and timing are handled" @(
      "Navdhya encourages families to share their preferred date, approximate locality, ceremony type, and any building or travel constraints early in the process.",
      "A realistic local booking flow helps the family set expectations around priest arrival, ritual duration, and what should already be arranged in the home.",
      "This is one reason city-specific pages are valuable: they connect abstract service interest with the practical rhythm of a real local booking."
    )),
    (New-Section "Why trust and verification matter even more in local bookings" @(
      "Local search intent often begins from urgency or convenience, but Navdhya emphasizes that priest verification, communication, and ceremony fit still matter more than speed alone.",
      "The family usually needs to know who is coming, what type of ceremony experience the priest has, and how the ritual will be explained or adapted to their household context.",
      "That trust layer is part of what turns a local search into a dependable ritual experience."
    )),
    (New-Section "How Navdhya helps families book with more confidence" @(
      "A strong local page should leave the reader with a clear next step. Navdhya therefore explains $keyword in terms of request clarity, preparation clarity, and service transparency rather than relying on generic location language alone.",
      "The more precise the family is about ceremony type, locality, timing, and household preferences, the better the service experience usually becomes.",
      "That is why Navdhya sees local booking education as part of trust-building, not just discoverability."
    ))
  )

  return Render-Sections @($sections + (Get-ExtendedSections $Entry))
}

function New-FAQData {
  param($Entry)

  $title = $Entry.title
  $keyword = $Entry.primaryKeyword
  $summary = $Entry.summary

  $sections = @(
    (New-Section "Why readers ask detailed questions about $keyword" @(
      "$title exists because many readers do not need a philosophical essay first. They need clear answers to the practical questions that come up before a booking or ceremony decision. Navdhya uses FAQ pages to meet that need directly. $summary",
      "This format works especially well when families want quick clarity on priest roles, timing, suitability, or what to expect from a service interaction.",
      "Navdhya still treats the subject seriously. The goal is short-form clarity without losing ritual or cultural depth."
    )),
    (New-Section "How Navdhya uses FAQ pages as decision support" @(
      "A strong FAQ page helps families move from uncertainty to action. Navdhya uses these pages to answer the questions people are most likely to search right before choosing a pandit, clarifying a role, or planning a ceremony.",
      "That means the page should be direct, practical, and rooted in real family concerns rather than abstract terminology.",
      "The result is a format that supports both search discoverability and genuine decision confidence."
    )),
    (New-Section "Why beginner and comparison questions deserve real answers" @(
      "Questions around $keyword may sound basic, but they usually sit very close to real booking decisions. Navdhya therefore answers them with care because a poor understanding at this stage can lead to the wrong priest choice or the wrong ceremony expectations.",
      "Good FAQ content reduces avoidable confusion. It helps families ask better follow-up questions and communicate more clearly with the service team.",
      "That is why even short-form content deserves editorial depth and practical usefulness."
    )),
    (New-Section "How Navdhya turns FAQs into practical guidance" @(
      "Navdhya treats FAQ pages as entry points into a larger knowledge system. A reader may start with one question, but the answer should leave them better prepared for booking, verification, ceremony planning, or priest communication.",
      "That is why the answers below focus on what families actually need to understand before moving forward.",
      "The stronger the initial clarity, the smoother the rest of the ritual journey usually becomes."
    ))
  )

  $faqItems = switch ($Entry.contentFamily) {
    "astrology-faq" { New-AstrologyFaqs $Entry }
    default { New-PanditFaqs $Entry }
  }

  return [ordered]@{
    sections = @($sections + (Get-ExtendedSections $Entry))
    faqs = $faqItems
  }
}

function Parse-SourceFile {
  param([string]$SourcePath)

  $raw = Get-Content -Raw $SourcePath
  $lines = $raw -split "`r?`n" | Where-Object { $_.Trim() }
  $entries = @()

  foreach ($line in $lines | Select-Object -Skip 1) {
    $parts = $line -split "\|", 9
    if ($parts.Count -lt 9) { continue }

    $entries += [ordered]@{
      slug = $parts[0].Trim()
      title = $parts[1].Trim()
      primaryKeyword = $parts[2].Trim()
      secondaryKeywords = Convert-ToPhraseList $parts[3]
      searchIntent = $parts[4].Trim().ToLower()
      schemaType = $parts[5].Trim()
      cluster = $parts[6].Trim()
      summary = $parts[7].Trim()
      contentFamily = $parts[8].Trim()
    }
  }

  return $entries
}

$allEntries = @()

foreach ($relativePath in $SourcePaths) {
  $fullPath = if ([System.IO.Path]::IsPathRooted($relativePath)) { $relativePath } else { Join-Path $root $relativePath }
  if (-not (Test-Path $fullPath)) { continue }
  $allEntries += Parse-SourceFile -SourcePath $fullPath
}

for ($i = 0; $i -lt $allEntries.Count; $i++) {
  $entry = $allEntries[$i]
  $metaTitle = New-MetaTitle $entry
  $metaDescription = New-MetaDescription $entry
  $relatedSlugs = Get-RelatedSlugs -Entries $allEntries -Index $i

  $faqs = @()
  $howToSteps = @()

  switch ($entry.schemaType) {
    "HowTo" {
      $data = New-HowToData $entry
      $pageData = Render-Sections $data.sections
      $faqs = Expand-FaqAnswers -Faqs (New-StandardFaqs $entry) -Entry $entry
      $howToSteps = $data.howToSteps
    }
    "Service" {
      $pageData = New-ServiceData $entry
      $faqs = Expand-FaqAnswers -Faqs (New-StandardFaqs $entry) -Entry $entry
    }
    "LocalBusiness" {
      $pageData = New-LocalBusinessData $entry
      $faqs = Expand-FaqAnswers -Faqs (New-StandardFaqs $entry) -Entry $entry
    }
    "FAQPage" {
      $data = New-FAQData $entry
      $pageData = Render-Sections $data.sections
      $faqs = Expand-FaqAnswers -Faqs $data.faqs -Entry $entry
    }
    default {
      $pageData = New-ArticleData $entry
      $faqs = Expand-FaqAnswers -Faqs (New-StandardFaqs $entry) -Entry $entry
    }
  }

  $page = [ordered]@{
    id = "nav-seo-$($entry.slug)"
    slug = $entry.slug
    cluster = $entry.cluster
    title = $entry.title
    metaTitle = $metaTitle
    metaDescription = $metaDescription
    primaryKeyword = $entry.primaryKeyword
    secondaryKeywords = @($entry.secondaryKeywords)
    searchIntent = $entry.searchIntent
    schemaType = $entry.schemaType
    content = $pageData.content
    outline = $pageData.outline
    relatedSlugs = @($relatedSlugs)
    lastUpdated = $lastUpdated
    author = Get-AuthorForEntry $entry
    readingTime = 1
    faqs = @($faqs)
    howToSteps = @($howToSteps)
  }

  $page.readingTime = Get-ReadingTime -Content $page.content -Faqs $page.faqs -HowToSteps $page.howToSteps

  $filePath = Join-Path $pagesDir "$($entry.slug).json"
  $json = $page | ConvertTo-Json -Depth 8
  [System.IO.File]::WriteAllText($filePath, $json, $utf8NoBom)
}

Write-Output "Generated $($allEntries.Count) expanded SEO pages."

