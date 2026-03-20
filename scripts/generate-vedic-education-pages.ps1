$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$sourcePath = Join-Path $root "content/sources/vedic-education-pages.tsv"
$pagesDir = Join-Path $root "content/pages"
$lastUpdated = "2026-03-19T00:00:00.000Z"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Convert-ToPhraseList {
  param([string]$value)
  return ($value -split ";" | ForEach-Object { $_.Trim() } | Where-Object { $_ })
}

function Get-AuthorForCluster {
  param([string]$cluster)
  switch ($cluster) {
    "faq" { return "Navdhya Education Team" }
    "pandit-services" { return "Navdhya Education Team" }
    "spiritual-guides" { return "Navdhya Education Team" }
    "festival-rituals" { return "Navdhya Education Team" }
    default { return "Navdhya Education Team" }
  }
}

function Get-RelatedSlugs {
  param(
    [array]$entries,
    [int]$index
  )

  $core = @("what-is-navdhya", "navdhya-vedic-authenticity", "navdhya-vedic-tradition")
  $neighbors = @()
  if ($index -gt 0) { $neighbors += $entries[$index - 1].slug }
  if ($index -lt ($entries.Count - 1)) { $neighbors += $entries[$index + 1].slug }
  if ($index -gt 1) { $neighbors += $entries[$index - 2].slug }
  if ($index -lt ($entries.Count - 2)) { $neighbors += $entries[$index + 2].slug }

  return ($core + $neighbors | Where-Object { $_ } | Select-Object -Unique | Select-Object -First 5)
}

function New-ArticleContent {
  param($entry)

  $keyword = $entry.primaryKeyword
  $title = $entry.title
  $summary = $entry.summary
  $secondary = $entry.secondaryKeywords
  $secondaryPhrase = if ($secondary.Count -gt 1) { "$($secondary[0]), $($secondary[1])" } elseif ($secondary.Count -eq 1) { $secondary[0] } else { $keyword }

  $sections = @(
    @{
      heading = "Why $keyword still matters in Hindu understanding"
      p1 = "Many readers begin with a simple question: why does $keyword matter in the first place. Navdhya approaches this topic as part of a larger effort to make Vedic learning more understandable for modern families. $summary"
      p2 = "The value of this subject is not limited to scholars or priests. In practice, topics such as $secondaryPhrase shape how families think about worship, duty, prayer, and the meaning of ritual in everyday life. That is why Navdhya treats the subject as something worth explaining with clarity rather than assuming prior knowledge."
      p3 = "For modern readers, the challenge is often not lack of respect for tradition, but lack of access to good explanation. Navdhya uses education pages like this one to help readers move from surface familiarity to a more stable understanding of why the concept continues to matter."
      p4 = "This fuller understanding is especially valuable for households trying to connect study with practice. When the meaning of a term becomes clearer, puja, ritual discussion, and family learning all become easier to approach with confidence."
    },
    @{
      heading = "The traditional background behind $title"
      p1 = "Every major ritual idea has a background in scripture, practice, or inherited teaching. Navdhya explains $keyword by placing it within that broader Hindu context so readers can understand not only the term, but also the devotional logic behind it."
      p2 = "When a concept is seen in context, it becomes easier to understand how it moved from textual tradition into living household practice. Navdhya uses that approach so the reader can connect doctrine, ceremony, and family observance more naturally."
      p3 = "This background also helps prevent common simplifications. A concept that seems small in isolation may carry a much deeper role when viewed against the long history of ritual, commentary, household custom, and the practical needs of worshippers across generations."
      p4 = "Readers often discover that what first looked like a narrow ritual detail is actually linked to broader ideas of duty, devotion, memory, symbolism, or sacred order. That layered background is one reason these guides aim for depth rather than quick summary alone."
    },
    @{
      heading = "How $keyword appears in real ritual life"
      p1 = "A concept becomes meaningful when it can be recognized in lived practice. Navdhya therefore explains how $keyword appears in ceremonies, home worship, preparation, or devotional reflection instead of leaving it as a definition only."
      p2 = "This practical framing matters because many families encounter the topic not in a classroom, but while preparing for a puja, watching a ceremony, or asking why a familiar custom exists. Navdhya builds its explanations around that real-world encounter."
      p3 = "When readers can connect the idea to ritual action, symbolism, or family observance, it stops feeling distant. Navdhya wants the subject to feel both intellectually clear and spiritually relevant, especially for households trying to keep tradition active in daily life."
      p4 = "Practical recognition also makes learning easier to remember. A reader who can identify the concept inside a known festival, ceremony, chant, or household custom is much more likely to retain the teaching and apply it in future worship."
    },
    @{
      heading = "Common misunderstandings and modern questions"
      p1 = "Modern readers often meet ancient terms through fragments, forwarded summaries, or social media explanations that remove context. Navdhya tries to correct that by addressing the most common misunderstandings around $keyword in a calm and grounded way."
      p2 = "That effort is especially useful when the subject touches ritual meaning, ethical behavior, or symbolic action. Clear explanation helps the reader move beyond superficial interpretation and see why the topic continues to matter in Hindu thought."
      p3 = "Many misunderstandings arise because a topic is detached from its proper ritual setting or reduced to a slogan. Navdhya responds by restoring explanation, examples, and devotional context so the reader can evaluate the idea more carefully."
      p4 = "This is one of the reasons educational writing still matters even in an age of quick answers. A concept becomes more useful and more faithful to tradition when readers are given enough context to think with care instead of reacting to fragments."
    },
    @{
      heading = "How Navdhya teaches this topic to families today"
      p1 = "Navdhya presents educational guides like this one because households often want learning that is respectful, practical, and relevant to ritual life. $keyword is easier to retain when it is explained alongside ceremony, worship, and household observance rather than as isolated theory."
      p2 = "This teaching style also supports intergenerational learning. Navdhya wants parents, younger readers, and first-time learners to be able to approach the subject with enough depth to appreciate it and enough clarity to discuss it meaningfully."
      p3 = "Education also supports confidence. Families are more likely to ask good questions, participate more intentionally, and preserve ritual continuity when explanation feels trustworthy. Navdhya sees that educational function as part of the wider spiritual service mission."
      p4 = "A clearer teaching style can also support conversation within the home. Parents, elders, and younger readers often need a shared vocabulary if they are going to discuss worship and ritual meaning in a way that strengthens family continuity."
    },
    @{
      heading = "Why learning about $keyword deepens practice"
      p1 = "Studying $keyword does more than answer a vocabulary question. It helps readers understand how Hindu ritual, devotion, and spiritual responsibility fit together. Navdhya sees that deeper understanding as one of the strongest reasons educational content matters."
      p2 = "When a family understands the meaning behind practice, worship often becomes more intentional and more rooted. That is why Navdhya continues to publish guides like this one: they help tradition remain not only inherited, but consciously understood."
      p3 = "In the long run, that kind of understanding strengthens both ritual participation and cultural continuity. Navdhya wants readers to leave the page with more than a definition - it wants them to leave with a framework that makes future worship and study feel richer."
      p4 = "That is ultimately why long-form education matters. When the subject is understood with enough patience and context, the reader is better prepared not only to describe it, but also to recognize its value inside living Hindu practice."
    }
  )

  $html = foreach ($section in $sections) {
    "<h2>$($section.heading)</h2><p>$($section.p1)</p><p>$($section.p2)</p><p>$($section.p3)</p><p>$($section.p4)</p>"
  }
  return @{
    content = ($html -join "")
    outline = $sections.heading
  }
}

function New-FaqPageContent {
  param($entry)

  $keyword = $entry.primaryKeyword
  $summary = $entry.summary

  $content = @(
    "<h2>Why readers ask about $keyword</h2><p>Navdhya created this FAQ page because readers often encounter $keyword in ritual conversations without getting clear, grounded answers. $summary</p><p>The goal of this page is to answer common beginner questions in a direct and accessible way so families can move from confusion to understanding without losing respect for the tradition.</p>",
    "<h2>How this FAQ is meant to help</h2><p>Some readers are preparing for a ritual, some are learning basic Hindu concepts for the first time, and others are trying to explain these ideas to younger family members. Navdhya uses FAQ pages like this one to make that learning process easier and more trustworthy.</p><p>Each question below is written to address a practical point of confusion while still keeping the answer rooted in respectful explanation.</p>",
    "<h2>Why beginner questions deserve serious answers</h2><p>Navdhya treats beginner questions seriously because confusion around foundational ideas can make ritual life feel distant or overly technical. A careful FAQ page helps readers approach the tradition with more confidence.</p><p>That confidence matters whether the reader is preparing for puja, speaking with elders, or trying to understand why a familiar practice is performed in a particular way.</p>",
    "<h2>How Navdhya uses FAQ pages in its education journey</h2><p>FAQ pages are one of the simplest ways Navdhya supports accessible learning. They answer high-frequency questions quickly while still pointing readers toward deeper study and more context-rich guides.</p><p>For readers who need a starting point rather than a long essay, this format can be especially helpful.</p>"
  ) -join ""

  $faqSeed = @(
    "What does $keyword mean in simple terms",
    "Why is $keyword important in Hindu practice",
    "How does $keyword appear in ritual life",
    "Is $keyword the same in every household tradition",
    "Why do modern readers misunderstand $keyword",
    "How can families explain $keyword to children",
    "Does learning about $keyword change how puja is understood",
    "How does Navdhya explain $keyword to beginners",
    "What is the most important takeaway about $keyword",
    "Why does this topic still matter today"
  )

  $faqs = foreach ($question in $faqSeed) {
    @{
      question = "$question?"
      answer = "Navdhya explains that $keyword should be understood through ritual context, family practice, and the broader summary of the topic: $summary This means beginners benefit most when the answer is tied to worship, lived tradition, and practical understanding rather than a bare definition alone."
    }
  }

  return @{
    content = $content
    outline = @("Why readers ask about $keyword", "How this FAQ is meant to help")
    faqs = $faqs
  }
}

$raw = Get-Content -Raw $sourcePath
$lines = $raw -split "`r?`n" | Where-Object { $_.Trim() }
$entries = @()

foreach ($line in $lines | Select-Object -Skip 1) {
  $parts = $line -split "\|", 10
  if ($parts.Count -lt 10) { continue }
  $entries += [ordered]@{
    slug = $parts[0].Trim()
    title = $parts[1].Trim()
    primaryKeyword = $parts[2].Trim()
    secondaryKeywords = Convert-ToPhraseList $parts[3]
    searchIntent = $parts[4].Trim().ToLower()
    schemaType = $parts[5].Trim()
    cluster = $parts[6].Trim()
    metaTitle = $parts[7].Trim()
    metaDescription = $parts[8].Trim()
    summary = $parts[9].Trim()
  }
}

for ($i = 0; $i -lt $entries.Count; $i++) {
  $entry = $entries[$i]
  $articleData = if ($entry.schemaType -eq "FAQPage") { New-FaqPageContent $entry } else { New-ArticleContent $entry }
  $relatedSlugs = Get-RelatedSlugs -entries $entries -index $i

  $page = [ordered]@{
    id = "nav-edu-$($entry.slug)"
    slug = $entry.slug
    cluster = $entry.cluster
    title = $entry.title
    metaTitle = $entry.metaTitle
    metaDescription = $entry.metaDescription
    primaryKeyword = $entry.primaryKeyword
    secondaryKeywords = $entry.secondaryKeywords
    searchIntent = $entry.searchIntent
    schemaType = $entry.schemaType
    content = $articleData.content
    outline = $articleData.outline
    relatedSlugs = $relatedSlugs
    lastUpdated = $lastUpdated
    author = Get-AuthorForCluster $entry.cluster
    readingTime = 6
    faqs = if ($entry.schemaType -eq "FAQPage") { $articleData.faqs } else {
      @(
        @{
          question = "Why is $($entry.primaryKeyword) important in Hindu understanding?"
          answer = "Navdhya explains that this topic matters because it connects ritual meaning, devotional practice, and the lived experience of Hindu family life."
        },
        @{
          question = "How does Navdhya explain $($entry.primaryKeyword) to beginners?"
          answer = "Navdhya uses context, examples from ritual life, and clear language so readers can understand the topic without losing its depth."
        },
        @{
          question = "Does learning about $($entry.primaryKeyword) help with puja or ritual practice?"
          answer = "Yes. Better understanding often helps readers participate in worship with more intention and less confusion."
        },
        @{
          question = "Why does this topic still matter today?"
          answer = "Navdhya presents it as a living part of Hindu tradition that continues to shape worship, values, and family observance."
        }
      )
    }
    howToSteps = @()
  }

  $filePath = Join-Path $pagesDir "$($entry.slug).json"
  $json = $page | ConvertTo-Json -Depth 6
  [System.IO.File]::WriteAllText($filePath, $json, $utf8NoBom)
}

Write-Output "Generated $($entries.Count) Vedic education pages."
