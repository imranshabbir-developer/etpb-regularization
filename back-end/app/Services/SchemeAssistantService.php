<?php

namespace App\Services;

/**
 * Answers questions about the scheme.
 *
 * This is a curated knowledge base with keyword matching, not a generative
 * model, and that is deliberate. The questions people ask here are legal ones —
 * am I eligible, what will I owe, what happens if I do not pay — and a wrong
 * answer costs a member of the public money or their home. Every answer below
 * is traceable to a clause of the Scheme 1977 or to the Board's own
 * instructions, and when nothing matches confidently the assistant says so and
 * points to the district office rather than inventing something.
 *
 * Answers, clauses and suggested questions are returned in the current locale;
 * keywords are listed in both English and Urdu so either language matches.
 */
class SchemeAssistantService
{
    /** Arabic and Persian code points that Urdu keyboards use interchangeably, and both digit sets. */
    private const URDU_FOLD = [
        "\u{064A}" => "\u{06CC}", "\u{0649}" => "\u{06CC}", "\u{0626}" => "\u{06CC}",
        "\u{0643}" => "\u{06A9}",
        "\u{0647}" => "\u{06C1}", "\u{0629}" => "\u{06C1}", "\u{06C0}" => "\u{06C1}", "\u{06C2}" => "\u{06C1}", "\u{06C3}" => "\u{06C1}",
        "\u{06D3}" => "\u{06D2}",
        "\u{0622}" => "\u{0627}", "\u{0623}" => "\u{0627}", "\u{0625}" => "\u{0627}", "\u{0671}" => "\u{0627}",
        "\u{0624}" => "\u{0648}",
        "\u{06F0}" => '0', "\u{06F1}" => '1', "\u{06F2}" => '2', "\u{06F3}" => '3', "\u{06F4}" => '4',
        "\u{06F5}" => '5', "\u{06F6}" => '6', "\u{06F7}" => '7', "\u{06F8}" => '8', "\u{06F9}" => '9',
        "\u{0660}" => '0', "\u{0661}" => '1', "\u{0662}" => '2', "\u{0663}" => '3', "\u{0664}" => '4',
        "\u{0665}" => '5', "\u{0666}" => '6', "\u{0667}" => '7', "\u{0668}" => '8', "\u{0669}" => '9',
    ];

    public function __construct(
        private readonly SettingService $settings,
    ) {
    }

    /**
     * @return array{answer: string, clause: ?string, matched: bool, suggestions: array<int,string>}
     */
    public function ask(string $question): array
    {
        $q = $this->normalise($question);

        if ($q === '') {
            return $this->fallback();
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($this->knowledge() as $entry) {
            $score = $this->score($q, $entry['keywords']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $entry;
            }
        }

        // Below this the match is coincidence rather than understanding.
        if ($best === null || $bestScore < 1.0) {
            return $this->fallback();
        }

        return [
            'answer'      => $best['answer'],
            'clause'      => $best['clause'] ?? null,
            'matched'     => true,
            'suggestions' => $best['followups'] ?? $this->topics(),
        ];
    }

    /** Opening prompts, so the widget is not a blank box. */
    public function topics(): array
    {
        return [
            __('Am I eligible for this scheme?'),
            __('How much is the fee and how do I pay it?'),
            __('What documents do I need?'),
            __('How is the rent decided?'),
            __('What are arrears and how much will I owe?'),
            __('How long does it take?'),
        ];
    }

    public function greeting(): string
    {
        return __('Ask me about the Regularization of Possession scheme — who can apply, what documents are needed, the Rs. :fee deposit, how rent and arrears are worked out, or what happens next.', ['fee' => $this->fee()]);
    }

    // ---- the knowledge base ------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function knowledge(): array
    {
        $fee = ['fee' => $this->fee()];

        return [
            [
                'keywords' => ['eligible', 'eligibility', 'qualify', 'who can apply', 'can i apply', 'am i eligible', 'entitled', 'can i apply', 'may i apply', 'do i qualify', 'am i allowed',
                               'اہل', 'اہلیت', 'حقدار', 'کون درخواست دے سکتا', 'درخواست دے سکتا', 'درخواست دے سکتی', 'درخواست دے سکتے', 'کیا میں درخواست', 'اجازت ہے'],
                'clause'   => __('Clause 3(ii)(a)'),
                'answer'   => $this->paragraphs(
                    __('You can apply if you were **in actual physical possession of the property prior to 1 January 2010**. Possession on or after that date is not accepted.'),
                    __('You also need to be able to show your possession with documents — a Jamabandi, mutation, Khasra Girdawari, old utility bills, a court order, or similar.'),
                    __('If you qualify, you are treated as a **tenant** of the Board and rent is fixed for the property. This scheme does not by itself make you the owner.'),
                ),
                'followups' => [__('What documents do I need?'), __('How is the rent decided?'), __('What if I took possession in 2012?')],
            ],
            [
                'keywords' => ['after 2010', '2011', '2012', '2015', '2020', 'took possession in 20', 'later than 2010', 'not before 2010',
                               '2010 کے بعد', '2010 کے بعد قبضہ', 'بعد میں قبضہ'],
                'clause'   => __('Clause 3(ii)(a)'),
                'answer'   => $this->paragraphs(
                    __('Unfortunately not. The scheme is only open to people who were in actual physical possession **prior to 1 January 2010**. If your possession began on or after that date the system will not accept the application, and the department cannot make an exception — the date is fixed by the Scheme itself.'),
                    __('If you believe your possession actually began earlier and you can prove it, bring the evidence to your district office.'),
                ),
            ],
            [
                'keywords' => ['fee', 'rs 5000', '5000', 'five thousand', 'deposit', 'pay order', 'demand draft', 'bankers cheque', 'how much to apply', 'application fee', 'payment', 'how much do i pay',
                               'how much do i have to pay', 'what do i have to pay', 'how much does it cost',
                               'cost to apply', 'charges', 'processing fee',
                               'فیس', 'فیس کتنی', 'زرِ ضمانت', 'ضمانت', 'پے آرڈر', 'ڈیمانڈ ڈرافٹ', 'بینکر چیک', 'بینکرز چیک', 'پانچ ہزار',
                               'کتنی رقم جمع', 'کتنے پیسے', 'اخراجات', 'خرچہ', 'چارجز'],
                'clause'   => __('Board requirement'),
                'answer'   => $this->paragraphs(
                    __('You must deposit **Rs. :fee** to have your application processed.', $fee),
                    $this->bullets([
                        __("Pay by **pay order, banker's cheque or demand draft**"),
                        __('Draw it in favour of **Chairman ETPB**'),
                        __('Then record the instrument details in the portal — bank, branch and branch code, and the date'),
                    ]),
                    __('Until Accounts confirms the instrument with the bank your application stays marked **PENDING** and **the department will not process it**. Once confirmed it becomes **PAID** and the officers begin work.'),
                    __('Recording the details in the portal is not the same as paying — the deposit still has to reach the bank.'),
                ),
                'followups' => [__('What happens after I pay?'), __('How long does it take?')],
            ],
            [
                'keywords' => ['document', 'documents', 'papers', 'evidence', 'jamabandi', 'mutation', 'khasra', 'girdawari', 'proof', 'what do i need', 'attach', 'upload',
                               'دستاویز', 'کاغذات', 'ثبوت', 'شواہد', 'جمع بندی', 'انتقال', 'خسرہ', 'گرداوری', 'اپ لوڈ', 'کیا درکار', 'درکار ہیں'],
                'clause'   => __('Clause 3(ii)(c)'),
                'answer'   => $this->paragraphs(
                    __('Regularization is decided on **documentary evidence**, or on a court order. The heads the department asks for are:'),
                    $this->bullets([
                        __('**Jamabandi** (record of rights) — certified copy'),
                        __('**Mutation** (intiqal) — certified copy'),
                        __('**Khasra Girdawari** — certified copy'),
                        __('**Geo coordinates** of the property'),
                        __('**Location plan**, and an approved building plan if there is one'),
                        __('**Electricity bill**, and gas or WASA bills if you have them'),
                        __('**Court order**, if any'),
                        __('**Affidavit** about your date of possession and your nominee'),
                        __('**CNIC copy**'),
                        __('**Nomination form**'),
                        __('Any other supporting document'),
                    ]),
                    __('The older your utility bills, the better — they help prove when your possession began. Documents marked as needing a certified copy must be certified.'),
                ),
                'followups' => [__('Am I eligible for this scheme?'), __('What is the nomination form?')],
            ],
            [
                'keywords' => ['rent', 'how is rent', 'rent decided', 'rent fixed', 'how much rent', 'assessment', 'assessed', 'fbr rate', 'dc rate', 'market rent', 'how much rent will', 'rent per month',
                               'monthly rent', 'who decides the rent', 'rate',
                               'کرای', 'کرایہ کیسے', 'کرایہ کیسے طے', 'کرایہ طے', 'کرایہ کون طے', 'کتنا کرایہ', 'ماہانہ کرایہ', 'مارکیٹ کرایہ',
                               'تشخیص', 'ایف بی آر', 'ڈی سی ریٹ', 'ریٹ', 'نرخ'],
                'clause'   => __('Clause 10'),
                'answer'   => $this->paragraphs(
                    __('The **District Officer** fixes the rent — you do not propose it.'),
                    __('He looks at the market rent and the rent of other properties nearby in similar circumstances, and considers:'),
                    $this->bullets([
                        __('the **FBR** notified rate'),
                        __('the **District Collector (DC)** rate'),
                        __('a **NESPAK or registered valuator** rate'),
                        __('the **actual rents** of adjoining properties'),
                    ]),
                    __('He then puts the proposed rent on public notice, allows **15 days** for objections, hears anyone who objects, and fixes the rent **giving written reasons**.'),
                    __('The rent then rises by **8% a year** and is re-assessed every six years.'),
                ),
                'followups' => [__('What are arrears and how much will I owe?'), __('Can I object to the rent?')],
            ],
            [
                'keywords' => ['arrear', 'arrears', 'back rent', 'owe', 'how much will i owe', 'past rent', 'dues', 'outstanding', 'how much do i owe', 'back payment', 'previous rent',
                               'بقایا', 'بقایہ', 'واجبات', 'واجب الادا', 'پچھلا کرایہ', 'سابقہ کرایہ', 'گزشتہ کرایہ', 'کتنی رقم ادا', 'میرے ذمے'],
                'clause'   => __('Clause 3(ii)(b)'),
                'answer'   => $this->paragraphs(
                    __('Arrears are the rent for the period you have already been in possession.'),
                    __('They are counted from the **earliest** of:'),
                    $this->bullets([
                        __('**1 July 2000**, or'),
                        __('the date you actually took possession, or'),
                        __('the date of a court judgment about the property'),
                    ]),
                    __('So if you have been in possession since 1998, arrears run from 1998 — not from 2000.'),
                    __('Because the rent rises 8% a year, arrears over twenty years or more can be a **large sum**. You must clear them before you can be treated as a tenant, but you do not necessarily have to pay it all at once.'),
                ),
                'followups' => [__('Can I pay in instalments?'), __('What if I cannot afford it?')],
            ],
            [
                'keywords' => ['instalment', 'installment', 'instalments', 'pay in parts', 'monthly payment', 'cannot pay at once', 'spread', 'in instalments', 'by instalments', 'part payment',
                               'pay slowly', 'easy instalments', 'kist',
                               'قسط', 'اقساط', 'اقساط میں', 'قسطوں میں', 'آسان اقساط', 'حصوں میں', 'یکمشت', 'یک مشت', 'تھوڑا تھوڑا'],
                'clause'   => __('Clause 13'),
                'answer'   => $this->paragraphs(
                    __('Yes. In deserving cases the District Officer may allow you to pay the arrears in **monthly instalments, up to 24 of them** — that is, over two years.'),
                    __('You have to ask, explain why paying in one sum is not possible, and the officer records his reasons for allowing it. Once a plan is approved, your application can move forward to approval even though the balance is not yet cleared.'),
                ),
                'followups' => [__('What if I cannot afford it?'), __('What are arrears and how much will I owe?')],
            ],
            [
                'keywords' => ['cannot afford', 'poor', 'indigent', 'widow', 'orphan', 'remission', 'waive', 'waiver', 'reduce', 'too expensive', 'hardship',
                               'استطاعت', 'غریب', 'نادار', 'مستحق', 'بیوہ', 'یتیم', 'معافی', 'معاف ہو', 'معاف کی', 'رعایت', 'برداشت نہیں', 'ادا نہیں کر سکتا', 'ادا نہیں کر سکتی'],
                'clause'   => __('Clause 12'),
                'answer'   => $this->paragraphs(
                    __('The **Chairman** can assess a nominal rent, or remit the rent or arrears altogether, for people who are **indigent, orphans, widows**, or otherwise unable to meet the liability.'),
                    __('Tick the relevant box when you apply, and be ready to evidence it. The District Officer proposes the remission and only the Chairman can grant it.'),
                    __('If remission is not granted, instalments under Clause 13 are the other route.'),
                ),
                'followups' => [__('Can I pay in instalments?')],
            ],
            [
                'keywords' => ['object', 'objection', 'objector', 'disagree', 'challenge', 'appeal', 'not happy', 'dispute the rent',
                               'اعتراض', 'اعتراض کیسے', 'کرایہ پر اعتراض', 'معترض', 'اختلاف', 'چیلنج', 'اپیل', 'مطمئن نہیں'],
                'clause'   => __('Clause 10(i)(c)–(d)'),
                'answer'   => $this->paragraphs(
                    __('Yes. When the District Officer proposes a rent he issues a public notice, and anyone — including you — has **15 days from receiving the notice** to object.'),
                    __('Objections must be heard. The rent cannot be fixed while an objection is undecided, and every objection is decided with written reasons. You are entitled to be heard before the rent is fixed.'),
                ),
                'followups' => [__('How is the rent decided?'), __('How long does it take?')],
            ],
            [
                'keywords' => ['how long', 'how much time', 'duration', 'timeline', 'when will', 'take time', 'delay', 'days',
                               'کتنا وقت', 'وقت لگ', 'کتنے دن', 'کتنی دیر', 'کب تک', 'مدت', 'تاخیر', 'دیر'],
                'clause'   => __('Clause 10(i)(e), Clause 3(ii)(d)'),
                'answer'   => $this->paragraphs(
                    __('The Scheme sets two deadlines on the department:'),
                    $this->bullets([
                        __('the **assessment of rent** must be completed within **60 days** of the first notice, extendable only by the Chairman'),
                        __('the **Administrator must approve** within **one month** of the decision, recording reasons'),
                    ]),
                    __('The clock does not start until your **Rs. :fee deposit is confirmed**, so paying promptly is the single biggest thing you control.', $fee),
                    __('Objections, hearings and any court case will add time. You can see exactly where your application stands at any moment in this portal.'),
                ),
                'followups' => [__('What happens after I pay?'), __('How much is the fee and how do I pay it?')],
            ],
            [
                'keywords' => ['what happens after', 'next step', 'what next', 'after i pay', 'after applying', 'process', 'procedure', 'stages',
                               'ادائیگی کے بعد', 'اس کے بعد', 'کے بعد کیا', 'اگلا مرحلہ', 'اگلے مراحل', 'مراحل', 'طریقہ کار', 'طریق کار', 'کارروائی', 'آگے کیا',
                               'درخواست دینے کے بعد', 'جمع کرانے کے بعد'],
                'clause'   => __('Clause 3(ii), Clause 10'),
                'answer'   => $this->paragraphs(
                    __('The steps are:'),
                    $this->numbered([
                        __('You file the application and record your **Rs. :fee** deposit', $fee),
                        __('**Accounts** confirms the deposit — your status changes from PENDING to **PAID**'),
                        __('The **District Officer** scrutinises your papers and inspects the site'),
                        __('He proposes a rent, issues a **public notice** and allows **15 days** for objections'),
                        __('Objections are heard and decided'),
                        __('He **fixes the rent** with written reasons, and the arrears are worked out'),
                        __('You clear the arrears, or an instalment plan or remission is approved'),
                        __('The **Administrator approves** the regularization, recording reasons'),
                        __('Your **nomination form** is taken and a **tenancy agreement** is executed'),
                        __('A **regularization order** is issued'),
                    ]),
                    __('You can follow every step of this in the portal.'),
                ),
                'followups' => [__('How long does it take?'), __('What is the nomination form?')],
            ],
            [
                'keywords' => ['nominee', 'nomination', 'nomination form', 'heir', 'heirs', 'after my death', 'inherit', 'legal heirs',
                               'نامزد', 'نامزدگی فارم', 'وارث', 'ورثا', 'وراثت', 'میری وفات', 'وفات کے بعد', 'انتقال کے بعد', 'مرنے کے بعد'],
                'clause'   => __('Scheme para 3(iii)(B)'),
                'answer'   => $this->paragraphs(
                    __('The nomination form names who should succeed to the tenancy after you, and lists your legal heirs.'),
                    __('It is **compulsory**. The Scheme is explicit that the District Officer shall not regularize the possession until the nomination form has been obtained — so the case cannot be completed without it.'),
                    __('You will be asked for it near the end, after approval and before the tenancy agreement is signed.'),
                ),
                'followups' => [__('What happens after I pay?')],
            ],
            [
                'keywords' => ['owner', 'ownership', 'malkiat', 'title', 'become owner', 'buy', 'purchase', 'transfer of ownership', 'will i own',
                               'do i own', 'i own the', 'own the property', 'own it', 'mine', 'sell', 'proprietor',
                               'مالک', 'مالک بن', 'ملکیت', 'مالکانہ', 'خرید', 'فروخت', 'بیچ سکتا', 'منتقلی', 'ٹائٹل', 'کرایہ دار'],
                'clause'   => __('Clause 3(ii)'),
                'answer'   => $this->paragraphs(
                    __('This scheme makes you a **recorded tenant** of the Evacuee Trust Property Board — not the owner.'),
                    __('Clause 3(ii) says an existing occupant whose possession has not been regularized "may be treated as tenant", a tenancy agreement is executed, and rent is fixed. That is what regularization means here.'),
                    __('Buying the property is a separate matter under different provisions of the Scheme, and would be a further application after your possession has been regularized. Ask your district office about that.'),
                ),
                'followups' => [__('Am I eligible for this scheme?'), __('How is the rent decided?')],
            ],
            [
                'keywords' => ['court', 'case', 'stay', 'restraining', 'litigation', 'sub judice', 'pending in court', 'judge',
                               'عدالت', 'عدالتی کارروائی', 'مقدمہ', 'مقدمے', 'حکم امتناع', 'امتناعی', 'اسٹے', 'سٹے', 'جج', 'زیر سماعت'],
                'clause'   => __('Clause 3(ii)(c)'),
                'answer'   => $this->paragraphs(
                    __('You must declare any court case about the property, any **restraining order or stay**, and any direction case.'),
                    __('While a case is pending or a stay is in force, the department **cannot proceed** with your application — it is parked as sub judice. It resumes automatically once the case is disposed of and no stay remains.'),
                    __('A court order in your favour can also serve as the basis for regularization in place of the usual documentary evidence.'),
                ),
                'followups' => [__('What documents do I need?')],
            ],
            [
                'keywords' => ['area', 'marla', 'kanal', 'sqft', 'square feet', 'measurement', 'how to enter area', 'sarsai', 'acre', 'convert',
                               'رقبہ', 'مرلہ', 'مرلے', 'کنال', 'سرسائی', 'ایکڑ', 'مربع فٹ', 'مربع گز', 'پیمائش'],
                'clause'   => __('Pakistani revenue measure'),
                'answer'   => $this->paragraphs(
                    __('Enter the area however your papers record it — square feet, square yards, Marla, Kanal or Acre — and the system converts it to square feet for you and shows the working.'),
                    __('On the **revenue standard** used for these properties:'),
                    $this->bullets([
                        __('1 Marla = **272.25 sqft**'),
                        __('1 Kanal = 20 Marla = **5,445 sqft**'),
                        __('1 Acre = 8 Kanal = **43,560 sqft**'),
                        __('1 Marla = 9 Sarsai'),
                    ]),
                    __('You can also enter a compound figure such as "2 Kanal 7 Marla 3 Sarsai" and it will be totalled correctly.'),
                    __('Note that some urban housing schemes use a 225 sqft Marla instead. Which one applies to your property is set by the district.'),
                ),
                'followups' => [__('What documents do I need?')],
            ],
            [
                'keywords' => ['status', 'track', 'where is my application', 'progress', 'check application', 'pending', 'what stage', 'is it approved', 'has it been approved',
                               'when will i know', 'application number',
                               'صورتحال', 'صورت حال', 'ٹریک', 'درخواست کہاں', 'کس مرحلے', 'پیش رفت', 'منظور ہو', 'منظوری ہو', 'زیر التوا', 'درخواست نمبر'],
                'clause'   => null,
                'answer'   => $this->paragraphs(
                    __('Sign in and open **My applications**. Each one shows:'),
                    $this->bullets([
                        __('whether your deposit is **PENDING** or **PAID**'),
                        __('which stage the case has reached'),
                        __('the rent fixed, if it has been'),
                        __('what you owe, and what you have paid'),
                        __('anything the department is waiting for from you'),
                    ]),
                    __('If your status says PENDING, the department has not started work — the deposit needs to be confirmed first.'),
                ),
                'followups' => [__('How much is the fee and how do I pay it?'), __('How long does it take?')],
            ],
            [
                'keywords' => ['contact', 'office', 'phone', 'help', 'speak to someone', 'complaint', 'address', 'visit',
                               'رابطہ', 'دفتر', 'فون', 'شکایت', 'مدد', 'ملاقات', 'کسی سے بات'],
                'clause'   => null,
                'answer'   => $this->paragraphs(
                    __('For anything this assistant cannot answer, contact the **ETPB district office** for the district where the property is.'),
                    __('Take your CNIC, your application number if you already have one, and your original documents. The Dealing Assistant there can also file an application on your behalf if you cannot use the portal yourself.'),
                ),
            ],
            [
                'keywords' => ['reject', 'rejected', 'refuse', 'refused', 'turned down', 'why was my application',
                               'مسترد', 'نامنظور', 'نا منظور', 'رد کر', 'رد ہو', 'درخواست کیوں'],
                'clause'   => __('Clause 3(ii)'),
                'answer'   => $this->paragraphs(
                    __('An application is usually refused for one of these reasons:'),
                    $this->bullets([
                        __('possession began **on or after 1 January 2010**'),
                        __('the **documentary evidence** does not establish possession'),
                        __('the **Rs. :fee deposit** was never confirmed', $fee),
                        __('a **court** has decided the matter against you'),
                    ]),
                    __('Every rejection must be recorded **with reasons**, and you can see those reasons on your application. If a document was simply missing or unclear, the application is usually returned to you to correct rather than rejected outright.'),
                ),
                'followups' => [__('What documents do I need?'), __('Am I eligible for this scheme?')],
            ],
        ];
    }

    // ---- answer layout -----------------------------------------------------
    // The widget renders blank-line paragraphs, "- " bullets and "1. " lists.

    private function paragraphs(string ...$blocks): string
    {
        return implode("\n\n", $blocks);
    }

    /** @param array<int, string> $items */
    private function bullets(array $items): string
    {
        return implode("\n", array_map(fn (string $item) => '- '.$item, $items));
    }

    /** @param array<int, string> $items */
    private function numbered(array $items): string
    {
        return implode("\n", array_map(fn (string $item, int $i) => ($i + 1).'. '.$item, $items, array_keys($items)));
    }

    // ---- matching ----------------------------------------------------------

    private function score(string $question, array $keywords): float
    {
        $score = 0.0;
        $joined = null;

        foreach ($keywords as $keyword) {
            $k = $this->normalise($keyword);
            if ($k === '') {
                continue;
            }

            $hit = str_contains($question, $k);

            // Urdu compounds are written both with and without the space
            // (بقایا جات / بقایاجات), so a multi-word Urdu phrase also matches run together.
            if (! $hit && str_contains($k, ' ') && preg_match('/[^\x00-\x7F]/', $k)) {
                $joined ??= str_replace(' ', '', $question);
                $hit = str_contains($joined, str_replace(' ', '', $k));
            }

            if ($hit) {
                // A longer phrase matching is far stronger evidence than a
                // single common word, so weight by the number of words in it.
                $score += 1.0 + (substr_count($k, ' ') * 0.75);
            }
        }

        return $score;
    }

    private function normalise(string $text): string
    {
        $text = strtr(mb_strtolower(trim($text)), self::URDU_FOLD);
        $text = preg_replace('/[\p{Mn}\x{0640}]+/u', '', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function fee(): string
    {
        return number_format((float) $this->settings->decimal('processing_fee', '5000.00'), 0);
    }

    /** @return array{answer: string, clause: ?string, matched: bool, suggestions: array<int,string>} */
    private function fallback(): array
    {
        return [
            'answer' => $this->paragraphs(
                __('I could not match that to anything I know reliably, and I would rather say so than guess about a legal matter.'),
                __('Try one of the questions below, or contact the **ETPB district office** for the district where the property is — they can answer anything specific to your case.'),
            ),
            'clause'      => null,
            'matched'     => false,
            'suggestions' => $this->topics(),
        ];
    }
}
