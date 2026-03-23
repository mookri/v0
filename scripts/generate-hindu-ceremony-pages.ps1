$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$sourcePath = Join-Path $root "content/sources/hindu-ceremony-pages.json"
$pagesDir = Join-Path $root "content/pages"
$lastUpdated = "2026-03-19T00:00:00.000Z"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Join-Words {
  param([string[]]$values)

  $clean = @($values | Where-Object { $_ -and $_.Trim() })
  if ($clean.Count -eq 0) { return "" }
  if ($clean.Count -eq 1) { return $clean[0] }
  if ($clean.Count -eq 2) { return "$($clean[0]) and $($clean[1])" }

  $lead = @($clean | Select-Object -First ($clean.Count - 1))
  return "$($lead -join ", "), and $($clean[-1])"
}

function Get-AuthorForEntry {
  param($entry)

  switch ($entry.cluster) {
    "festival-rituals" { return "Navdhya Festival Guides Team" }
    "ritual-guides" { return "Navdhya Ritual Guides Team" }
    default { return "Navdhya Ceremonies Team" }
  }
}

function New-MarriageContent {
  param($entry)

  $moments = Join-Words $entry.ritualMoments

  $content = @(
    "<h2>Why the $($entry.primaryKeyword) is treated as a sacred union</h2><p>The $($entry.primaryKeyword) is never understood in Hindu life as only a social celebration. It is treated as a dharmic union in which two people enter a shared household responsibility while their families, elders, and sacred witnesses affirm the bond. Navdhya explains the ceremony in this deeper frame because many modern readers encounter the wedding through visible moments alone and miss the ritual logic holding the sequence together. $($entry.summary)</p><p>When that deeper logic is understood, the ceremony begins to feel much more coherent. Each step, whether brief or elaborate, serves the larger idea that marriage is a vowed partnership ordered around sacred responsibility, family continuity, and mutual support.</p>",
    "<h2>How preparation shapes the meaning of the wedding</h2><p>Before the visible marriage rites begin, the planning of the $($entry.primaryKeyword) already reflects ritual seriousness. Families think about muhurta, the role of the officiating pandit, the order of offerings, and the way the couple will participate in key vows. Navdhya highlights this stage because preparation determines whether the wedding feels rushed and decorative or grounded and intentional.</p><p>This is especially important for families balancing venue management and tradition. $($entry.modernAngle) The best ceremony experience usually comes when the household knows which elements are essential, how the flow is structured, and how practical coordination can support ritual dignity rather than interrupt it.</p>",
    "<h2>The major ritual moments that define the ceremony</h2><p>In most forms of the $($entry.primaryKeyword), the central actions include $moments. These moments are not interchangeable gestures. They work together to establish consent, offering, witness, vow, and household blessing. Navdhya explains them as a sequence so readers understand why the ceremony feels cumulative rather than symbolic in a loose sense.</p><p>Some rites visibly involve the couple, while others express the role of parents, elders, and the officiating priest. What unites them is the shared understanding that marriage is entered through sacred action, spoken promise, and the acknowledgment of divine witness.</p>",
    "<h2>Why mantras, fire, and witness matter in Hindu marriage</h2><p>One of the defining features of the $($entry.primaryKeyword) is that meaning is carried not only by objects or gestures but also by recitation and sacred presence. Fire is treated as witness, mantras articulate intention, and offerings make the ceremony participatory rather than passive. Navdhya often finds that modern couples appreciate the wedding far more when they understand the mantras are not ornamental sound but part of the vow-bearing structure of the rite.</p><p>This is why the role of the pandit remains so important. The priest is not merely hosting an event. He is guiding a structured rite in which sequence, pronunciation, and sacred timing all matter to the integrity of the ceremony.</p>",
    "<h2>Regional variation changes form but not the ritual core</h2><p>No single wedding format exhausts the diversity of Hindu marriage practice. Families may follow North Indian, South Indian, Bengali, Gujarati, Maharashtrian, or other regional traditions, and each brings distinctive custom. Navdhya explains the ceremony in a way that respects that diversity while also showing the enduring core beneath it. The outer form may vary, but the deeper marriage logic of vow, witness, blessing, and household transition remains recognizable.</p><p>This perspective helps readers avoid the mistake of assuming that one visible variation makes another less authentic. In Hindu tradition, ritual plurality is common. What matters is understanding how local custom fits within the wider grammar of sacred marriage.</p>",
    "<h2>Why families still seek clear guidance today</h2><p>Many urban families want the $($entry.primaryKeyword) to feel both traditional and intelligible. They do not want a ceremony reduced to stage management, but they also do not want elders and younger relatives separated by confusion about what is happening. Navdhya sees strong explanatory content as part of how families recover the inner meaning of the wedding while still managing the realities of contemporary life.</p><p>That is also why guides like this matter for search. People search for the meaning of the ritual because they want clarity, not just logistics. A well-explained ceremony becomes easier to honor, easier to remember, and easier to carry forward within the life of the family.</p>"
  ) -join ""

  return @{
    content = $content
    outline = @(
      "Why the $($entry.primaryKeyword) is treated as a sacred union",
      "How preparation shapes the meaning of the wedding",
      "The major ritual moments that define the ceremony",
      "Why mantras, fire, and witness matter in Hindu marriage",
      "Regional variation changes form but not the ritual core",
      "Why families still seek clear guidance today"
    )
  }
}

function New-HomeContent {
  param($entry)

  $moments = Join-Words $entry.ritualMoments

  $content = @(
    "<h2>Why entering a home is treated as a ceremonial beginning</h2><p>The $($entry.primaryKeyword) is one of the clearest examples of how Hindu ritual connects space with spiritual intention. A home is not viewed only as a property asset or a convenient shelter. It becomes the place in which family life, prayer, hospitality, memory, and duty will unfold. Navdhya explains the ceremony in that spirit so readers understand why the ritual is approached with reverence. $($entry.summary)</p><p>When that perspective is understood, the ceremony stops looking like a symbolic add-on and starts to feel like an intentional beginning. The home is welcomed into sacred family life, and the family enters the space with gratitude rather than simple occupancy.</p>",
    "<h2>Preparation begins before anyone crosses the threshold</h2><p>A thoughtful $($entry.primaryKeyword) begins with planning. Families usually think about date selection, home readiness, altar placement, materials, and who will participate in the core rites. Navdhya highlights this stage because domestic ceremonies succeed when the household feels prepared rather than hurried. That is especially true when move-in logistics are already consuming attention.</p><p>$($entry.modernAngle) Clear preparation helps the ritual feel less fragmented. The family can then focus on the meaning of the ceremony instead of improvising basic decisions at the last minute.</p>",
    "<h2>The core sequence of the ritual</h2><p>In most forms of the $($entry.primaryKeyword), the major actions include $moments. These steps sanctify the home, align the space with auspicious intention, and invite blessing for the family that will live there. Navdhya presents the sequence as a ritual journey rather than a checklist because each step builds on the last.</p><p>The threshold crossing itself is often memorable because it marks the visible transition from outside to inside, from preparation to inhabitation. Yet the whole sequence matters. The blessing of the space, the invocation of deities, and the fire or prayer elements all contribute to the feeling that the home is being entered consciously.</p>",
    "<h2>Why domestic rituals feel so relevant for modern households</h2><p>Home-related ceremonies have stayed important because they speak directly to family life. In busy cities, many households still want their new beginning to be marked with sanctity even if the setting is an apartment tower rather than a large ancestral house. Navdhya explains the ritual in a way that meets those real conditions without diluting the seriousness of the observance.</p><p>That practicality matters. Families often need to know what is essential, how to use limited space, and how to include elders, children, and guests in a way that still preserves the sacred feel of the event.</p>",
    "<h2>How the ritual combines atmosphere, prayer, and intention</h2><p>One reason the $($entry.primaryKeyword) is so memorable is that it is not built on one act alone. Cleanliness, decoration, altar arrangement, mantra, offerings, and family participation all contribute to the atmosphere. Navdhya emphasizes this because ceremony is experienced through environment as much as instruction. A well-held atmosphere helps the home feel blessed in a way the family will remember.</p><p>That atmosphere is not superficial. It gives visible form to the deeper idea that domestic life should begin with right intention, gratitude, and humility before the sacred.</p>",
    "<h2>Why families search for guidance before the ceremony</h2><p>Families usually look up the $($entry.primaryKeyword) because they want reassurance that they are getting the sequence and meaning right. They may be asking about muhurta, Vastu shanti, materials, or how much of the ritual can be adapted to modern living constraints. Navdhya sees that search behavior as a sign that families still care deeply about entering a home in the right spirit.</p><p>That is why clear content matters. When households understand the ritual better, they can participate more calmly, coordinate more intelligently, and preserve a sense of sanctity even in the midst of a major life transition.</p>",
    "<h2>What a well-planned griha pravesh leaves behind</h2><p>A well-conducted $($entry.primaryKeyword) does more than complete a single morning of ritual. It leaves the family with a stronger sense that the home has been entered thoughtfully and that the first memories formed there began with blessing rather than haste. Navdhya treats that emotional residue as an important part of the ceremony, because people often remember not just what was done, but how grounded the entire beginning felt.</p><p>This is one reason home-entry rituals remain such strong search topics. Families are looking for a way to begin domestic life with seriousness, peace, and auspicious intent. Better explanation helps that desire take a more practical and meaningful form.</p>"
  ) -join ""

  return @{
    content = $content
    outline = @(
      "Why entering a home is treated as a ceremonial beginning",
      "Preparation begins before anyone crosses the threshold",
      "The core sequence of the ritual",
      "Why domestic rituals feel so relevant for modern households",
      "How the ritual combines atmosphere, prayer, and intention",
      "Why families search for guidance before the ceremony",
      "What a well-planned griha pravesh leaves behind"
    )
  }
}

function New-SamskaraContent {
  param($entry)

  $moments = Join-Words $entry.ritualMoments

  $content = @(
    "<h2>Why the $($entry.primaryKeyword) matters within the samskara tradition</h2><p>The $($entry.primaryKeyword) belongs to a wider Hindu understanding of life-stage rites in which important transitions are marked with blessing, structure, and sacred intention. Navdhya explains this ceremony through that samskara framework because it helps families see why the rite has enduring value beyond custom alone. $($entry.summary)</p><p>When readers understand the ceremony as part of a larger life journey, the ritual feels more coherent. It becomes a way of welcoming change responsibly rather than simply observing an inherited family habit.</p>",
    "<h2>How families usually prepare for the ceremony</h2><p>Preparation for the $($entry.primaryKeyword) often begins with questions about timing, the role of the priest, the comfort of the child or participant, and the basic flow of the ritual. Navdhya addresses these practical questions because life-stage ceremonies are often deeply emotional for families and need a calmer planning path. A clearer outline reduces anxiety and helps the rite feel more intentional.</p><p>$($entry.modernAngle) That modern context does not reduce the seriousness of the ceremony. It simply changes the practical factors the family needs to account for while preserving the ritual center.</p>",
    "<h2>The major actions that shape the rite</h2><p>In most forms of the $($entry.primaryKeyword), the ceremony includes $moments. These actions create the visible structure of the rite, but their importance lies in what they signify: blessing, transition, purification, or intentional entry into a new stage of life. Navdhya explains the order of events so families can appreciate how the rite unfolds rather than viewing it as a handful of disconnected customs.</p><p>Many samskaras are remembered most vividly because of one visible act, yet the surrounding prayers and blessings matter just as much. They place the visible moment inside a larger spiritual frame.</p>",
    "<h2>Why timing, blessing, and family participation all matter</h2><p>A samskara is rarely a private action carried out in isolation. Elders, parents, relatives, and the officiating priest often help create the emotional and ritual texture of the event. Navdhya highlights this because the rite is not only about the individual participant. It is also about how the family acknowledges a new stage with shared responsibility and blessing.</p><p>That shared participation is one reason these ceremonies remain so meaningful. They allow households to translate affection, duty, and prayer into a visible act of support.</p>",
    "<h2>How traditional meaning stays relevant in modern family life</h2><p>Many readers search for the $($entry.primaryKeyword) because they want to understand how an old rite still belongs in contemporary life. Navdhya approaches that question directly. The ceremony remains relevant not because every family performs it in exactly the same way, but because it gives form to values families still care about: blessing, continuity, gratitude, and mindful transition.</p><p>That is especially true for urban families trying to preserve meaningful observance while working within smaller households, tighter schedules, or geographically dispersed relatives.</p>",
    "<h2>Why clearer explanation helps families honor the rite well</h2><p>Good ceremony guidance does not replace tradition. It helps families approach tradition with more confidence. Navdhya sees educational pages on the $($entry.primaryKeyword) as part of that support system. When the meaning, sequence, and intention are clearer, families participate more intentionally and the ceremony is easier to remember for the right reasons.</p><p>In that sense, explanation is itself a form of care. It allows a life-stage ritual to remain dignified, accessible, and spiritually intelligible across generations.</p>",
    "<h2>How these rites strengthen continuity across generations</h2><p>The $($entry.primaryKeyword) also matters because it creates a teachable family memory. Younger relatives watch the rite, elders explain its purpose, and the household gains a shared reference point for future observance. Navdhya sees this intergenerational effect as one of the strongest reasons samskaras continue to matter in urban and modern contexts.</p><p>When a life-stage ceremony is explained well, it becomes easier for the family to preserve not only the event itself but also the values attached to it. That continuity is part of what makes samskara literature and ceremony guides so valuable over time.</p>"
  ) -join ""

  return @{
    content = $content
    outline = @(
      "Why the $($entry.primaryKeyword) matters within the samskara tradition",
      "How families usually prepare for the ceremony",
      "The major actions that shape the rite",
      "Why timing, blessing, and family participation all matter",
      "How traditional meaning stays relevant in modern family life",
      "Why clearer explanation helps families honor the rite well",
      "How these rites strengthen continuity across generations"
    )
  }
}

function New-FuneralContent {
  param($entry)

  $moments = Join-Words $entry.ritualMoments

  $content = @(
    "<h2>Why Hindu funeral rites are approached with reverence and care</h2><p>The $($entry.primaryKeyword) occupy a uniquely sensitive place in Hindu life because they are performed at a moment of grief, duty, and spiritual transition. Navdhya explains these rites with care because families often search for clarity while carrying emotional strain. $($entry.summary)</p><p>The goal of guidance here is not to flatten a solemn subject into procedure. It is to help readers understand how the rites honor the departed, support the family, and express sacred responsibility in the face of loss.</p>",
    "<h2>How Antyesti fits within the wider samskara framework</h2><p>Antyesti is understood as the final samskara, the last rite that marks the completion of embodied life. Navdhya explains this context because it helps readers see why the funeral sequence includes both immediate rites and later observances. The moment of cremation is important, but it is not the whole story.</p><p>There is a wider logic at work: honoring the departed, assisting the soul's onward journey in the ritual imagination, and giving the family a structured way to carry grief through duty, prayer, and remembrance.</p>",
    "<h2>The main stages families usually encounter</h2><p>In most descriptions of $($entry.primaryKeyword), families encounter stages such as $moments. These rites can vary in form depending on region, family custom, and circumstance, yet the overall arc remains recognizable. Navdhya explains the sequence so families understand which acts belong to the immediate funeral context and which belong to later remembrance.</p><p>That distinction matters because many readers understandably merge funeral rites, shraddha, and periodic ancestor observances into one idea. Clear explanation helps them see how the stages relate without becoming confused.</p>",
    "<h2>Why priestly guidance and family duty both matter</h2><p>At a time of loss, structured guidance can be especially important. The officiating priest helps preserve ritual order, but family duty remains central as well. Navdhya highlights this balance because the rites are not performed by ritual experts alone. They are carried by kinship, obligation, memory, and the emotional presence of the family.</p><p>That is also why funeral content needs a respectful tone. Families are not looking for spectacle or abstract theory. They are looking for calm explanation that can help them act with dignity when clarity matters most.</p>",
    "<h2>How modern families can understand the sequence without confusion</h2><p>$($entry.modernAngle) Navdhya approaches this need by separating the stages clearly and explaining what belongs to immediate last rites, what comes afterward, and how related observances fit into the wider framework of remembrance. That kind of clarity is especially useful when family members are coordinating from different cities or generations.</p><p>Modern conditions may change logistics, but they do not remove the need for meaning. Families still want to know why each step exists and how it should be approached with reverence.</p>",
    "<h2>Why careful explanation remains important</h2><p>Few topics demand clearer explanation than $($entry.primaryKeyword). In moments of grief, households need language that is steady, respectful, and practical. Navdhya treats this educational role seriously because good guidance can reduce confusion and help families focus on duty, prayer, and remembrance.</p><p>That is the deeper purpose of pages like this: to provide understanding at a time when families need both information and spiritual steadiness. A better explained rite is easier to perform with dignity and easier to remember with peace.</p>",
    "<h2>Why compassionate structure helps grieving families</h2><p>During bereavement, structure itself can be a form of support. The $($entry.primaryKeyword) offers a sequence through which emotion, memory, and duty can be carried with greater steadiness. Navdhya explains this not to make grief procedural, but to show why ritual order can be comforting when families feel overwhelmed.</p><p>That compassionate structure is one reason last-rites guidance continues to matter so much. Families need explanation that is humane as well as accurate, and careful educational content can help provide exactly that balance.</p>"
  ) -join ""

  return @{
    content = $content
    outline = @(
      "Why Hindu funeral rites are approached with reverence and care",
      "How Antyesti fits within the wider samskara framework",
      "The main stages families usually encounter",
      "Why priestly guidance and family duty both matter",
      "How modern families can understand the sequence without confusion",
      "Why careful explanation remains important",
      "Why compassionate structure helps grieving families"
    )
  }
}

function New-DeityContent {
  param($entry)

  $moments = Join-Words $entry.ritualMoments

  $content = @(
    "<h2>Why the $($entry.primaryKeyword) remains spiritually important</h2><p>The $($entry.primaryKeyword) continues to matter because it gives households a structured way to express devotion, gratitude, and sacred attention. Navdhya explains the ceremony in this fuller devotional frame so readers can understand not only what is done but why the rite feels meaningful in lived practice. $($entry.summary)</p><p>For many households, the ceremony is familiar in name but not always in sequence. A clear guide makes it easier to connect repeated family practice with the deeper logic of worship.</p>",
    "<h2>How the ceremony is usually prepared</h2><p>Every successful $($entry.primaryKeyword) begins with thoughtful preparation. Families usually consider the altar, the materials, the order of offerings, and who will lead or participate in the rite. Navdhya emphasizes this preparation stage because ritual confidence starts before the first mantra is recited. Good preparation helps worship feel calm instead of improvised.</p><p>$($entry.modernAngle) A better prepared ceremony usually feels both more reverent and more intelligible to the whole family.</p>",
    "<h2>The central sequence of offerings and prayer</h2><p>Most forms of the $($entry.primaryKeyword) include $moments. These actions work together to create a devotional progression. The worshipper prepares the space, invokes divine presence, makes offerings, and completes the rite through prayer, aarti, or prasadam. Navdhya explains the sequence carefully because many readers benefit from seeing how each act fits into the larger movement of worship.</p><p>Even when households simplify the ceremony, the inner pattern remains recognizable. Intention, invocation, offering, praise, and completion all continue to matter.</p>",
    "<h2>Why symbolism matters as much as visible action</h2><p>One reason the $($entry.primaryKeyword) endures is that it speaks through symbol as well as procedure. Lamps, flowers, grains, leaves, sweets, water, incense, or specific deity-associated items all carry meaning. Navdhya explains that symbolism because ritual becomes more powerful when the family understands what the offerings express.</p><p>This symbolic layer also helps younger participants connect to the ceremony. They are more likely to remember the rite when they understand why each act is performed rather than simply copying movement.</p>",
    "<h2>How households adapt the ceremony without losing its core</h2><p>No two homes conduct the $($entry.primaryKeyword) in exactly the same way. Regional custom, family tradition, available space, and level of ritual familiarity all shape the visible form. Navdhya explains the ceremony with enough flexibility to respect those differences while keeping the devotional core intact. What matters most is that the worship remains sincere, orderly, and grounded in understanding.</p><p>That flexibility is especially helpful for city households, nuclear families, or people returning to ritual life after a long gap. They often need guidance that is faithful without being intimidating.</p>",
    "<h2>Why readers search for this ceremony today</h2><p>People search for the $($entry.primaryKeyword) because they want more than a short list of materials. They want to know what the ceremony means, how it unfolds, and how to participate with confidence. Navdhya treats that search intent seriously because good explanation can transform worship from routine into conscious devotional practice.</p><p>That is why long-form ceremony pages matter. They help families recover the inner structure of worship and give ritual continuity a stronger place in contemporary life.</p>",
    "<h2>What deeper understanding changes in actual worship</h2><p>When a family understands the $($entry.primaryKeyword) more clearly, the worship experience often changes in tone. Participation becomes steadier, offerings feel less mechanical, and children or first-time participants have a better chance of understanding what they are witnessing. Navdhya sees that practical gain as one of the strongest reasons devotional education matters.</p><p>A more informed ritual does not have to become complicated. It simply becomes more conscious. That shift from habit to understanding can make even a familiar puja feel renewed and spiritually focused.</p><p>It also helps households sustain consistency. When the rite is understood better, the family is more likely to repeat it with care, explain it well to younger members, and preserve a devotional atmosphere that feels intentional rather than improvised.</p>"
  ) -join ""

  return @{
    content = $content
    outline = @(
      "Why the $($entry.primaryKeyword) remains spiritually important",
      "How the ceremony is usually prepared",
      "The central sequence of offerings and prayer",
      "Why symbolism matters as much as visible action",
      "How households adapt the ceremony without losing its core",
      "Why readers search for this ceremony today",
      "What deeper understanding changes in actual worship"
    )
  }
}

function New-FestivalContent {
  param($entry)

  $moments = Join-Words $entry.ritualMoments

  $content = @(
    "<h2>Why the $($entry.primaryKeyword) holds a special place in festival worship</h2><p>The $($entry.primaryKeyword) is significant because it turns a calendar observance into a disciplined act of devotion. Navdhya explains this ceremony as part of the larger rhythm of festival worship, where households prepare not only offerings and altars but also attention, memory, and gratitude. $($entry.summary)</p><p>Festival rituals endure because they gather theology, family continuity, and atmosphere into one shared practice. A clearer guide helps readers understand why these observances still feel powerful today.</p>",
    "<h2>How families prepare for the observance</h2><p>Preparation for the $($entry.primaryKeyword) often begins with cleaning the space, arranging the altar, gathering the right offerings, and understanding the day or days on which the worship should be centered. Navdhya emphasizes preparation because festival observances become more meaningful when the household knows how to create sacred order rather than approaching the ritual casually.</p><p>$($entry.modernAngle) Good preparation protects the devotional mood and helps the family participate more fully even when daily schedules are demanding.</p>",
    "<h2>The main ritual sequence across the observance</h2><p>Most forms of the $($entry.primaryKeyword) include $moments. These steps create the devotional architecture of the observance. Families invoke, offer, praise, remember, and complete the worship within a rhythm that is both structured and emotionally resonant. Navdhya lays out the sequence so the observance feels coherent instead of fragmented into isolated customs.</p><p>That structure matters especially during multi-day festivals, where continuity from one day to the next helps the entire observance feel spiritually cumulative.</p>",
    "<h2>Why symbolism and devotion both matter in the festival setting</h2><p>Festival worship is often visually rich, but the symbolism is not decorative alone. Colors, lamps, flowers, food offerings, recitations, and gestures all help communicate the meaning of the $($entry.primaryKeyword). Navdhya highlights these layers because readers often want to move from familiar appearance to deeper understanding.</p><p>Once that symbolic layer is clearer, the observance becomes easier to teach to children and easier to sustain with greater intention across years.</p>",
    "<h2>How modern households keep the observance meaningful</h2><p>Many families today maintain festival worship alongside work, school, travel, and apartment living. Navdhya explains the $($entry.primaryKeyword) with that reality in mind. The aim is not to reduce the observance, but to help households identify the devotional core, preserve the flow, and approach the rite with seriousness even when conditions are not ideal.</p><p>This kind of clarity is one reason festival guides remain so useful in digital search. Readers want help translating inherited observance into confident present-day practice.</p>",
    "<h2>Why festival ceremony guides still matter</h2><p>Searches for the $($entry.primaryKeyword) often come from readers who already know the festival by name but want to understand its inner order. Navdhya sees that as an opportunity to support devotional continuity. Better explanation helps a family go beyond repetition and into conscious worship.</p><p>That is why festival ceremony content deserves depth. It serves not only SEO demand but also the long-term work of making tradition easier to practice with understanding and reverence.</p>",
    "<h2>How festival discipline becomes part of household memory</h2><p>The $($entry.primaryKeyword) often leaves a lasting impression because it is tied to repeated seasonal memory. Children remember the lights, songs, offerings, and rhythm of worship long after the day ends. Navdhya sees that memory-making effect as one reason festival observances remain such important entry points into Hindu ritual life.</p><p>When the observance is explained with more depth, those memories become even more meaningful. They carry not only atmosphere but understanding, which makes the tradition easier to preserve across generations.</p><p>That is also why festival guides deserve real depth. The observance may last a day or a season, but the understanding a family builds during it can shape devotional habits for many years afterward. Families often return to those explanations every season, and each return strengthens both confidence and continuity.</p>"
  ) -join ""

  return @{
    content = $content
    outline = @(
      "Why the $($entry.primaryKeyword) holds a special place in festival worship",
      "How families prepare for the observance",
      "The main ritual sequence across the observance",
      "Why symbolism and devotion both matter in the festival setting",
      "How modern households keep the observance meaningful",
      "Why festival ceremony guides still matter",
      "How festival discipline becomes part of household memory"
    )
  }
}

function New-HowToContent {
  param($entry)

  $moments = Join-Words $entry.ritualMoments

  $content = @(
    "<h2>Why families look for a clear $($entry.primaryKeyword)</h2><p>Families usually search for a $($entry.primaryKeyword) because they want more than a list of items. They want a sequence they can follow with confidence and a devotional logic that helps the ceremony feel spiritually complete. Navdhya explains the rite in that practical spirit so a household can move from preparation to worship without unnecessary confusion. $($entry.summary)</p><p>That guidance matters because even well-known household pujas can feel uncertain when materials, recitation order, and family participation are not clearly organized in advance.</p>",
    "<h2>What to prepare before the puja begins</h2><p>A strong $($entry.primaryKeyword) begins with preparation of the place, the altar, and the core materials. Families usually gather the deity image, kalash or puja setup items, flowers, fruits, prasad ingredients, lamps, and any text needed for recitation. Navdhya emphasizes this stage because a calm start makes the rest of the ritual easier to follow.</p><p>$($entry.modernAngle) Good preparation also helps the family keep the focus on devotion instead of scrambling for missing items in the middle of the puja.</p>",
    "<h2>The main sequence of the observance</h2><p>In most homes, the $($entry.primaryKeyword) includes $moments. The exact detail can vary, but the broader sequence remains stable: prepare, invoke, offer, recite, complete, and share the blessing. Navdhya lays out this sequence clearly so first-time or returning worshippers can understand not just what to do, but why the order matters.</p><p>When the sequence is clearer, the puja feels far more participatory. Family members know when to join, when to listen, and how the devotional flow is unfolding.</p>",
    "<h2>Why katha, offering, and prasad are all essential parts of the rite</h2><p>The $($entry.primaryKeyword) is meaningful because it combines hearing, offering, and shared blessing. The recitation carries the narrative and devotional teaching of the observance, while naivedya and aarti complete the offering side of worship. Navdhya explains this balance because many readers know one element better than the others and want to understand how they fit together.</p><p>Prasad distribution is especially important because it extends the puja into shared family experience. The ritual does not end with recitation alone; it concludes through gratitude and distribution of blessed food.</p>",
    "<h2>How families adapt the puja to modern household life</h2><p>Many families perform the $($entry.primaryKeyword) in apartments, compact homes, or busy urban schedules. Navdhya addresses that reality by focusing on sequence, understanding, and respectful preparation rather than assuming a large traditional setup. What matters most is not scale, but sincerity, clarity, and orderly observance.</p><p>This approach helps households keep the puja meaningful even when time, space, or ritual familiarity are limited. Good guidance reduces hesitation and makes the observance easier to repeat over time.</p>",
    "<h2>Why a step-by-step guide remains valuable</h2><p>A detailed $($entry.primaryKeyword) remains valuable because readers often want confidence before they begin. They want to know the order, the materials, and the devotional purpose of each phase. Navdhya provides that clarity so the rite can be performed with more steadiness and less uncertainty.</p><p>That is also why how-to content matters for search and for practice. The search itself reflects a sincere wish to perform the ritual properly, and a well-structured guide helps turn that wish into a more grounded observance.</p>",
    "<h2>How repeated observance becomes easier over time</h2><p>Once a family understands the $($entry.primaryKeyword) clearly, repeated observance usually becomes much easier. Materials can be gathered more confidently, the altar can be arranged more calmly, and the family can focus more on devotion than uncertainty. Navdhya includes this practical perspective because many households are not just preparing for one puja; they are building repeatable confidence for future observances as well.</p><p>That repeatability is an important part of ritual continuity. A clear guide helps the first observance happen well, but it also helps the household preserve the practice with far less hesitation in the future.</p>"
  ) -join ""

  return @{
    content = $content
    outline = @(
      "Why families look for a clear $($entry.primaryKeyword)",
      "What to prepare before the puja begins",
      "The main sequence of the observance",
      "Why katha, offering, and prasad are all essential parts of the rite",
      "How families adapt the puja to modern household life",
      "Why a step-by-step guide remains valuable",
      "How repeated observance becomes easier over time"
    )
  }
}

function New-PagePayload {
  param($entry)

  switch ($entry.ceremonyFamily) {
    "marriage" { return New-MarriageContent $entry }
    "home" { return New-HomeContent $entry }
    "samskara" { return New-SamskaraContent $entry }
    "funeral" { return New-FuneralContent $entry }
    "festival" { return New-FestivalContent $entry }
    "howto-deity" { return New-HowToContent $entry }
    default { return New-DeityContent $entry }
  }
}

$entries = Get-Content -Raw $sourcePath | ConvertFrom-Json

foreach ($entry in $entries) {
  $payload = New-PagePayload $entry

  $page = [ordered]@{
    id = "nav-ceremony-$($entry.slug)"
    slug = $entry.slug
    cluster = $entry.cluster
    title = $entry.title
    metaTitle = $entry.metaTitle
    metaDescription = $entry.metaDescription
    primaryKeyword = $entry.primaryKeyword
    secondaryKeywords = @($entry.secondaryKeywords)
    searchIntent = $entry.searchIntent
    schemaType = $entry.schemaType
    content = $payload.content
    outline = $payload.outline
    relatedSlugs = @($entry.relatedSlugs)
    lastUpdated = $lastUpdated
    author = Get-AuthorForEntry $entry
    readingTime = 7
    faqs = @(
      @{
        question = "Why is the $($entry.primaryKeyword) still important for families today?"
        answer = "Navdhya explains that the ceremony remains important because it gives families a structured way to bring devotion, blessing, memory, and intentional observance into a major life, household, or festival moment while preserving a meaningful link to tradition."
      },
      @{
        question = "Do all households perform the $($entry.primaryKeyword) in exactly the same way?"
        answer = "No. Regional custom, family tradition, local language, and practical conditions can shape the visible form, but the central ritual meaning and devotional purpose usually remain recognizable across households and across different family settings."
      },
      @{
        question = "How does understanding the ritual sequence help?"
        answer = "A clearer sequence helps families participate more calmly, coordinate better with the officiating priest, and appreciate why each step matters instead of treating the ceremony as a set of disconnected customs or stage actions that happen without context."
      },
      @{
        question = "How does Navdhya help readers approach the $($entry.primaryKeyword)?"
        answer = "Navdhya uses practical explanation, ritual context, and family-oriented guidance so readers can approach the ceremony with more confidence, better understanding, and a clearer sense of devotional purpose before the ritual day arrives."
      },
      @{
        question = "What usually makes the ceremony feel more meaningful in practice?"
        answer = "Families usually experience the ceremony more deeply when they understand the sequence, prepare the required materials in advance, and know how the rite connects prayer, symbolism, household participation, and the emotional significance of the occasion."
      }
    )
    howToSteps = @()
  }

  if ($entry.schemaType -eq "HowTo") {
    $page.howToSteps = @(
      $entry.howToSteps | ForEach-Object {
        @{
          name = $_
          text = $_
        }
      }
    )
  }

  $filePath = Join-Path $pagesDir "$($entry.slug).json"
  $json = $page | ConvertTo-Json -Depth 8
  [System.IO.File]::WriteAllText($filePath, $json, $utf8NoBom)
}

Write-Output "Generated $($entries.Count) Hindu ceremony pages."
