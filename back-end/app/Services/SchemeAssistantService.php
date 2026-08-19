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
 */
class SchemeAssistantService
{
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
            'answer'      => $this->interpolate($best['answer']),
            'clause'      => $best['clause'] ?? null,
            'matched'     => true,
            'suggestions' => $best['followups'] ?? $this->topics(),
        ];
    }

    /** Opening prompts, so the widget is not a blank box. */
    public function topics(): array
    {
        return [
            'Am I eligible for this scheme?',
            'How much is the fee and how do I pay it?',
            'What documents do I need?',
            'How is the rent decided?',
            'What are arrears and how much will I owe?',
            'How long does it take?',
        ];
    }

    public function greeting(): string
    {
        return 'Ask me about the Regularization of Possession scheme — who can apply, '
             . 'what documents are needed, the Rs. ' . number_format((float) $this->settings->decimal('processing_fee', '5000.00'), 0)
             . ' deposit, how rent and arrears are worked out, or what happens next.';
    }

    // ---- the knowledge base ------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function knowledge(): array
    {
        return [
            [
                'keywords' => ['eligible', 'eligibility', 'qualify', 'who can apply', 'can i apply', 'am i eligible', 'entitled', 'can i apply', 'may i apply', 'do i qualify', 'am i allowed'],
                'clause'   => 'Clause 3(ii)(a)',
                'answer'   => 'You can apply if you were **in actual physical possession of the property before '
                            . '1 January 2010**. Possession on or after that date is not accepted.

You also need to be able to show your possession with documents — a Jamabandi, mutation, Khasra Girdawari, '
                            . 'old utility bills, a court order, or similar.

If you qualify, you are treated as a **tenant** of the Board and rent is fixed for the property. '
                            . 'This scheme does not by itself make you the owner.',
                'followups' => ['What documents do I need?', 'How is the rent decided?', 'What if I took possession in 2012?'],
            ],
            [
                'keywords' => ['after 2010', '2011', '2012', '2015', '2020', 'took possession in 20', 'later than 2010', 'not before 2010'],
                'clause'   => 'Clause 3(ii)(a)',
                'answer'   => 'Unfortunately not. The scheme is only open to people who were in possession '
                            . '**before 1 January 2010**. If your possession began on or after that date the '
                            . 'system will not accept the application, and the department cannot make an exception — '
                            . 'the date is fixed by the Scheme itself.

If you believe your possession actually began earlier and you can prove it, bring the evidence to your '
                            . 'district office.',
            ],
            [
                'keywords' => ['fee', 'rs 5000', '5000', 'five thousand', 'deposit', 'pay order', 'demand draft', 'bankers cheque', 'how much to apply', 'application fee', 'payment', 'how much do i pay',
                               'how much do i have to pay', 'what do i have to pay', 'how much does it cost',
                               'cost to apply', 'charges', 'processing fee'],
                'clause'   => 'Board requirement',
                'answer'   => 'You must deposit **Rs. :fee** to have your application processed.

- Pay by **pay order, banker&rsquo;s cheque or demand draft**
- Draw it in favour of **Chairman ETPB**
- Then record the instrument details in the portal — bank, branch and branch code, and the date

Until Accounts confirms the instrument with the bank your application stays marked **PENDING** and '
                            . '**the department will not process it**. Once confirmed it becomes **PAID** and the '
                            . 'officers begin work.

Recording the details in the portal is not the same as paying — the deposit still has to reach the bank.',
                'followups' => ['What happens after I pay?', 'How long does it take?'],
            ],
            [
                'keywords' => ['document', 'documents', 'papers', 'evidence', 'jamabandi', 'mutation', 'khasra', 'girdawari', 'proof', 'what do i need', 'attach', 'upload'],
                'clause'   => 'Clause 3(ii)(c)',
                'answer'   => 'Regularization is decided on **documentary evidence**, or on a court order. '
                            . 'The heads the department asks for are:

- **Jamabandi** (record of rights) — certified copy
- **Mutation** (intiqal) — certified copy
- **Khasra Girdawari** — certified copy
- **Geo coordinates** of the property
- **Location plan**, and an approved building plan if there is one
- **Electricity bill**, and gas or WASA bills if you have them
- **Court order**, if any
- **Affidavit** about your date of possession and your nominee
- **CNIC copy**
- **Nomination form**
- Any other supporting document

The older your utility bills, the better — they help prove when your possession began. '
                            . 'Documents marked as needing a certified copy must be certified.',
                'followups' => ['Am I eligible for this scheme?', 'What is the nomination form?'],
            ],
            [
                'keywords' => ['rent', 'how is rent', 'rent decided', 'rent fixed', 'how much rent', 'assessment', 'assessed', 'fbr rate', 'dc rate', 'market rent', 'how much rent will', 'rent per month',
                               'monthly rent', 'who decides the rent', 'rate'],
                'clause'   => 'Clause 10',
                'answer'   => 'The **District Officer** fixes the rent — you do not propose it.

He looks at the market rent and the rent of other properties nearby in similar circumstances, and considers:

- the **FBR** notified rate
- the **District Collector (DC)** rate
- a **NESPAK or registered valuator** rate
- the **actual rents** of adjoining properties

He then puts the proposed rent on public notice, allows **15 days** for objections, hears anyone who '
                            . 'objects, and fixes the rent **giving written reasons**.

The rent then rises by **8% a year** and is re-assessed every six years.',
                'followups' => ['What are arrears and how much will I owe?', 'Can I object to the rent?'],
            ],
            [
                'keywords' => ['arrear', 'arrears', 'back rent', 'owe', 'how much will i owe', 'past rent', 'dues', 'outstanding', 'how much do i owe', 'back payment', 'previous rent'],
                'clause'   => 'Clause 3(ii)(b)',
                'answer'   => 'Arrears are the rent for the period you have already been in possession.

They are counted from the **earliest** of:

- **1 July 2000**, or
- the date you actually took possession, or
- the date of a court judgment about the property

So if you have been in possession since 1998, arrears run from 1998 — not from 2000.

Because the rent rises 8% a year, arrears over twenty years or more can be a **large sum**. '
                            . 'You must clear them before you can be treated as a tenant, but you do not necessarily '
                            . 'have to pay it all at once.',
                'followups' => ['Can I pay in instalments?', 'What if I cannot afford it?'],
            ],
            [
                'keywords' => ['instalment', 'installment', 'instalments', 'pay in parts', 'monthly payment', 'cannot pay at once', 'spread', 'in instalments', 'by instalments', 'part payment',
                               'pay slowly', 'easy instalments', 'kist'],
                'clause'   => 'Clause 13',
                'answer'   => 'Yes. In deserving cases the District Officer may allow you to pay the arrears in '
                            . '**monthly instalments, up to 24 of them** — that is, over two years.

You have to ask, explain why paying in one sum is not possible, and the officer records his reasons '
                            . 'for allowing it. Once a plan is approved, your application can move forward to '
                            . 'approval even though the balance is not yet cleared.',
                'followups' => ['What if I cannot afford it?', 'What are arrears and how much will I owe?'],
            ],
            [
                'keywords' => ['cannot afford', 'poor', 'indigent', 'widow', 'orphan', 'remission', 'waive', 'waiver', 'reduce', 'too expensive', 'hardship'],
                'clause'   => 'Clause 12',
                'answer'   => 'The **Chairman** can assess a nominal rent, or remit the rent or arrears altogether, '
                            . 'for people who are **indigent, orphans, widows**, or otherwise unable to meet the liability.

Tick the relevant box when you apply, and be ready to evidence it. The District Officer proposes the '
                            . 'remission and only the Chairman can grant it.

If remission is not granted, instalments under Clause 13 are the other route.',
                'followups' => ['Can I pay in instalments?'],
            ],
            [
                'keywords' => ['object', 'objection', 'objector', 'disagree', 'challenge', 'appeal', 'not happy', 'dispute the rent'],
                'clause'   => 'Clause 10(i)(c)–(d)',
                'answer'   => 'Yes. When the District Officer proposes a rent he issues a public notice, and '
                            . 'anyone — including you — has **15 days from receiving the notice** to object.

Objections must be heard. The rent cannot be fixed while an objection is undecided, and every objection '
                            . 'is decided with written reasons. You are entitled to be heard before the rent is fixed.',
                'followups' => ['How is the rent decided?', 'How long does it take?'],
            ],
            [
                'keywords' => ['how long', 'how much time', 'duration', 'timeline', 'when will', 'take time', 'delay', 'days'],
                'clause'   => 'Clause 10(i)(e), Clause 3(ii)(d)',
                'answer'   => 'The Scheme sets two deadlines on the department:

- the **assessment of rent** must be completed within **60 days** of the first notice, extendable only by the Chairman
- the **Administrator must approve** within **one month** of the decision, recording reasons

The clock does not start until your **Rs. :fee deposit is confirmed**, so paying promptly is the '
                            . 'single biggest thing you control.

Objections, hearings and any court case will add time. You can see exactly where your application '
                            . 'stands at any moment in this portal.',
                'followups' => ['What happens after I pay?', 'How much is the fee and how do I pay it?'],
            ],
            [
                'keywords' => ['what happens after', 'next step', 'what next', 'after i pay', 'after applying', 'process', 'procedure', 'stages'],
                'clause'   => 'Clause 3(ii), Clause 10',
                'answer'   => 'The steps are:

1. You file the application and record your **Rs. :fee** deposit
2. **Accounts** confirms the deposit — your status changes from PENDING to **PAID**
3. The **District Officer** scrutinises your papers and inspects the site
4. He proposes a rent, issues a **public notice** and allows **15 days** for objections
5. Objections are heard and decided
6. He **fixes the rent** with written reasons, and the arrears are worked out
7. You clear the arrears, or an instalment plan or remission is approved
8. The **Administrator approves** the regularization, recording reasons
9. Your **nomination form** is taken and a **tenancy agreement** is executed
10. A **regularization order** is issued

You can follow every step of this in the portal.',
                'followups' => ['How long does it take?', 'What is the nomination form?'],
            ],
            [
                'keywords' => ['nominee', 'nomination', 'nomination form', 'heir', 'heirs', 'after my death', 'inherit', 'legal heirs'],
                'clause'   => 'Scheme para 3(iii)(B)',
                'answer'   => 'The nomination form names who should succeed to the tenancy after you, and lists '
                            . 'your legal heirs.

It is **compulsory**. The Scheme is explicit that the District Officer shall not regularize the '
                            . 'possession until the nomination form has been obtained — so the case cannot be '
                            . 'completed without it.

You will be asked for it near the end, after approval and before the tenancy agreement is signed.',
                'followups' => ['What happens after I pay?'],
            ],
            [
                'keywords' => ['owner', 'ownership', 'malkiat', 'title', 'become owner', 'buy', 'purchase', 'transfer of ownership', 'will i own',
                               'do i own', 'i own the', 'own the property', 'own it', 'mine', 'sell', 'proprietor'],
                'clause'   => 'Clause 3(ii)',
                'answer'   => 'This scheme makes you a **recorded tenant** of the Evacuee Trust Property Board — '
                            . 'not the owner.

Clause 3(ii) says an existing occupant whose possession has not been regularized "may be treated as '
                            . 'tenant", a tenancy agreement is executed, and rent is fixed. That is what '
                            . 'regularization means here.

Buying the property is a separate matter under different provisions of the Scheme, and would be a '
                            . 'further application after your possession has been regularized. Ask your district '
                            . 'office about that.',
                'followups' => ['Am I eligible for this scheme?', 'How is the rent decided?'],
            ],
            [
                'keywords' => ['court', 'case', 'stay', 'restraining', 'litigation', 'sub judice', 'pending in court', 'judge'],
                'clause'   => 'Clause 3(ii)(c)',
                'answer'   => 'You must declare any court case about the property, any **restraining order or stay**, '
                            . 'and any direction case.

While a case is pending or a stay is in force, the department **cannot proceed** with your application — '
                            . 'it is parked as sub judice. It resumes automatically once the case is disposed of and '
                            . 'no stay remains.

A court order in your favour can also serve as the basis for regularization in place of the usual '
                            . 'documentary evidence.',
                'followups' => ['What documents do I need?'],
            ],
            [
                'keywords' => ['area', 'marla', 'kanal', 'sqft', 'square feet', 'measurement', 'how to enter area', 'sarsai', 'acre', 'convert'],
                'clause'   => 'Pakistani revenue measure',
                'answer'   => 'Enter the area however your papers record it — square feet, square yards, Marla, '
                            . 'Kanal or Acre — and the system converts it to square feet for you and shows the working.

On the **revenue standard** used for these properties:

- 1 Marla = **272.25 sqft**
- 1 Kanal = 20 Marla = **5,445 sqft**
- 1 Acre = 8 Kanal = **43,560 sqft**
- 1 Marla = 9 Sarsai

You can also enter a compound figure such as "2 Kanal 7 Marla 3 Sarsai" and it will be totalled correctly.

Note that some urban housing schemes use a 225 sqft Marla instead. Which one applies to your property '
                            . 'is set by the district.',
                'followups' => ['What documents do I need?'],
            ],
            [
                'keywords' => ['status', 'track', 'where is my application', 'progress', 'check application', 'pending', 'what stage', 'is it approved', 'has it been approved',
                               'when will i know', 'application number'],
                'clause'   => null,
                'answer'   => 'Sign in and open **My applications**. Each one shows:

- whether your deposit is **PENDING** or **PAID**
- which stage the case has reached
- the rent fixed, if it has been
- what you owe, and what you have paid
- anything the department is waiting for from you

If your status says PENDING, the department has not started work — the deposit needs to be confirmed first.',
                'followups' => ['How much is the fee and how do I pay it?', 'How long does it take?'],
            ],
            [
                'keywords' => ['contact', 'office', 'phone', 'help', 'speak to someone', 'complaint', 'address', 'visit'],
                'clause'   => null,
                'answer'   => 'For anything this assistant cannot answer, contact the **ETPB district office** for '
                            . 'the district where the property is.

Take your CNIC, your application number if you already have one, and your original documents. '
                            . 'The Dealing Assistant there can also file an application on your behalf if you '
                            . 'cannot use the portal yourself.',
            ],
            [
                'keywords' => ['reject', 'rejected', 'refuse', 'refused', 'turned down', 'why was my application'],
                'clause'   => 'Clause 3(ii)',
                'answer'   => 'An application is usually refused for one of these reasons:

- possession began **on or after 1 January 2010**
- the **documentary evidence** does not establish possession
- the **Rs. :fee deposit** was never confirmed
- a **court** has decided the matter against you

Every rejection must be recorded **with reasons**, and you can see those reasons on your application. '
                            . 'If a document was simply missing or unclear, the application is usually returned to '
                            . 'you to correct rather than rejected outright.',
                'followups' => ['What documents do I need?', 'Am I eligible for this scheme?'],
            ],
        ];
    }

    // ---- matching ----------------------------------------------------------

    private function score(string $question, array $keywords): float
    {
        $score = 0.0;

        foreach ($keywords as $keyword) {
            $k = $this->normalise($keyword);
            if ($k === '') {
                continue;
            }

            if (str_contains($question, $k)) {
                // A longer phrase matching is far stronger evidence than a
                // single common word, so weight by the number of words in it.
                $score += 1.0 + (substr_count($k, ' ') * 0.75);
            }
        }

        return $score;
    }

    private function normalise(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    private function interpolate(string $answer): string
    {
        return str_replace(
            ':fee',
            number_format((float) $this->settings->decimal('processing_fee', '5000.00'), 0),
            $answer,
        );
    }

    /** @return array{answer: string, clause: ?string, matched: bool, suggestions: array<int,string>} */
    private function fallback(): array
    {
        return [
            'answer' => 'I could not match that to anything I know reliably, and I would rather say so '
                      . 'than guess about a legal matter.

Try one of the questions below, or contact the **ETPB district office** for the district where the '
                      . 'property is — they can answer anything specific to your case.',
            'clause'      => null,
            'matched'     => false,
            'suggestions' => $this->topics(),
        ];
    }
}
