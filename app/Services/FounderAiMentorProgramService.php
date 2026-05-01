<?php

namespace App\Services;

use App\Models\AiCurriculumLesson;
use App\Models\CompanyIntelligence;
use App\Models\Founder;
use App\Models\FounderActionPlan;
use App\Models\FounderWeeklyState;
use Illuminate\Support\Carbon;

class FounderAiMentorProgramService
{
    public function ensureFounderProgram(Founder $founder): void
    {
        $company = $founder->company;
        $intelligence = $company?->intelligence;

        if (!$company || !$intelligence || !$this->intelligenceIsComplete($founder, $intelligence)) {
            return;
        }

        $this->ensureCentralCurriculum();

        $programStart = $this->resolveProgramStartDate($founder);
        $this->seedFounderLessons($founder, $programStart);
        $this->seedFounderTasks($founder, $company, $intelligence);
        $this->completePlaceholderTask($founder);
        $this->refreshWeeklyState($founder);
    }

    public function ensureCentralCurriculum(): void
    {
        $rows = collect($this->curriculumBlueprint())
            ->map(fn (array $lesson): array => $lesson + [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        AiCurriculumLesson::query()->upsert(
            $rows,
            ['sequence'],
            ['week_number', 'day_number', 'source_book', 'slug', 'title', 'summary', 'article_body', 'action_prompt', 'is_active', 'updated_at']
        );
    }

    private function seedFounderLessons(Founder $founder, Carbon $programStart): void
    {
        $lessons = AiCurriculumLesson::query()
            ->where('is_active', true)
            ->orderBy('sequence')
            ->get();

        foreach ($lessons as $lesson) {
            FounderActionPlan::firstOrCreate(
                [
                    'founder_id' => $founder->id,
                    'context' => 'lesson',
                    'title' => $lesson->title,
                ],
                [
                    'description' => $lesson->summary,
                    'platform' => 'atlas',
                    'context' => 'lesson',
                    'priority' => max(40, 220 - $lesson->sequence),
                    'status' => 'pending',
                    'cta_label' => 'Open lesson',
                    'cta_url' => route('founder.learning-plan'),
                    'available_on' => $programStart->copy()->addDays(max(0, $lesson->sequence - 1))->toDateString(),
                    'metadata_json' => [
                        'program_source' => 'ai_mentor_foundation',
                        'sequence' => $lesson->sequence,
                        'week_number' => $lesson->week_number,
                        'day_number' => $lesson->day_number,
                        'source_book' => $lesson->source_book,
                        'summary' => $lesson->summary,
                        'article_body' => $lesson->article_body,
                        'action_prompt' => $lesson->action_prompt,
                    ],
                ]
            );
        }
    }

    private function seedFounderTasks(Founder $founder, $company, CompanyIntelligence $intelligence): void
    {
        foreach ($this->starterTasksForFounder($company, $intelligence) as $task) {
            FounderActionPlan::updateOrCreate(
                [
                    'founder_id' => $founder->id,
                    'context' => 'task',
                    'title' => $task['title'],
                ],
                [
                    'description' => $task['description'],
                    'platform' => $task['platform'],
                    'priority' => $task['priority'],
                    'status' => 'pending',
                    'cta_label' => $task['cta_label'],
                    'cta_url' => $task['cta_url'],
                    'available_on' => now()->toDateString(),
                    'metadata_json' => [
                        'program_source' => 'ai_mentor_foundation',
                        'summary' => $task['description'],
                        'article_body' => $task['description'],
                    ],
                ]
            );
        }
    }

    private function completePlaceholderTask(Founder $founder): void
    {
        FounderActionPlan::query()
            ->where('founder_id', $founder->id)
            ->where('title', 'Complete company intelligence')
            ->update([
                'context' => 'task',
                'status' => 'completed',
                'completed_at' => now(),
            ]);
    }

    private function refreshWeeklyState(Founder $founder): void
    {
        $totals = $founder->actionPlans()
            ->selectRaw("
                SUM(CASE WHEN context = 'task' AND (status NOT IN ('completed', 'complete', 'done') AND completed_at IS NULL) THEN 1 ELSE 0 END) as open_tasks_count,
                SUM(CASE WHEN context = 'task' AND (status IN ('completed', 'complete', 'done') OR completed_at IS NOT NULL) THEN 1 ELSE 0 END) as completed_tasks_count,
                SUM(CASE WHEN context = 'lesson' AND (status NOT IN ('completed', 'complete', 'done') AND completed_at IS NULL) THEN 1 ELSE 0 END) as open_lessons_count,
                SUM(CASE WHEN context = 'lesson' AND (status IN ('completed', 'complete', 'done') OR completed_at IS NOT NULL) THEN 1 ELSE 0 END) as completed_lessons_count
            ")
            ->first();

        $totalLessons = (int) ($totals?->open_lessons_count ?? 0) + (int) ($totals?->completed_lessons_count ?? 0);
        $progress = $totalLessons > 0
            ? (int) round(((int) ($totals?->completed_lessons_count ?? 0) / $totalLessons) * 100)
            : 0;

        FounderWeeklyState::updateOrCreate(
            ['founder_id' => $founder->id],
            [
                'open_tasks' => (int) ($totals?->open_tasks_count ?? 0),
                'completed_tasks' => (int) ($totals?->completed_tasks_count ?? 0),
                'open_milestones' => (int) ($totals?->open_lessons_count ?? 0),
                'completed_milestones' => (int) ($totals?->completed_lessons_count ?? 0),
                'weekly_focus' => 'Complete today\'s AI mentor lesson and move the highest-priority founder tasks forward.',
                'weekly_progress_percent' => max(0, min(100, $progress)),
                'state_updated_at' => now(),
            ]
        );
    }

    private function resolveProgramStartDate(Founder $founder): Carbon
    {
        $existingLesson = $founder->actionPlans()
            ->where('context', 'lesson')
            ->whereNotNull('available_on')
            ->orderBy('available_on')
            ->first();

        if ($existingLesson?->available_on) {
            return Carbon::parse($existingLesson->available_on)->startOfDay();
        }

        return now()->startOfDay();
    }

    private function intelligenceIsComplete(Founder $founder, CompanyIntelligence $intelligence): bool
    {
        $company = $founder->company;
        $required = [
            trim((string) $founder->full_name),
            trim((string) ($company?->company_name ?? '')),
            trim((string) ($company?->company_brief ?? '')),
            trim((string) ($company?->business_model ?? '')),
            trim((string) ($intelligence->target_audience ?? '')),
            trim((string) ($intelligence->primary_icp_name ?? '')),
            trim((string) ($intelligence->ideal_customer_profile ?? '')),
            trim((string) ($intelligence->problem_solved ?? '')),
            trim((string) ($intelligence->core_offer ?? '')),
            trim((string) ($intelligence->differentiators ?? '')),
            trim((string) ($intelligence->objections ?? '')),
            trim((string) ($intelligence->buying_triggers ?? '')),
            trim((string) ($intelligence->brand_voice ?? '')),
            trim((string) ($intelligence->visual_style ?? '')),
            trim((string) ($intelligence->primary_growth_goal ?? '')),
            trim((string) ($intelligence->known_blockers ?? '')),
        ];

        foreach ($required as $value) {
            if ($value === '') {
                return false;
            }
        }

        return true;
    }

    private function starterTasksForFounder($company, CompanyIntelligence $intelligence): array
    {
        $audience = trim((string) $intelligence->target_audience);
        $offer = trim((string) $intelligence->core_offer);
        $problem = trim((string) $intelligence->problem_solved);
        $businessModel = strtolower(trim((string) ($company->business_model ?? 'hybrid')));

        $channelTask = $businessModel === 'product'
            ? [
                'title' => 'Prepare your first storefront offer',
                'description' => 'Turn "' . $offer . '" into one concrete starter product with a clear price, one strong promise, and one reason to buy now.',
                'platform' => 'bazaar',
                'priority' => 92,
                'cta_label' => 'Open Bazaar',
                'cta_url' => route('workspace.launch', ['module' => 'bazaar']),
            ]
            : [
                'title' => 'Prepare your first bookable service',
                'description' => 'Turn "' . $offer . '" into one bookable service with clear outcome, duration, price, and the first trust-building promise.',
                'platform' => 'servio',
                'priority' => 92,
                'cta_label' => 'Open Servio',
                'cta_url' => route('workspace.launch', ['module' => 'servio']),
            ];

        return [
            [
                'title' => 'Write your one-sentence offer',
                'description' => 'Write a simple offer statement for ' . $audience . ': what you help them achieve, how you solve "' . $problem . '", and why your approach is worth attention.',
                'platform' => 'atlas',
                'priority' => 98,
                'cta_label' => 'Open Atlas',
                'cta_url' => route('workspace.launch', ['module' => 'atlas']),
            ],
            [
                'title' => 'List 25 named leads to contact first',
                'description' => 'Create a starting lead list of 25 real people or businesses inside your target audience so the founder sprint moves from theory into outreach.',
                'platform' => 'os',
                'priority' => 96,
                'cta_label' => 'Open Lead Tracker',
                'cta_url' => route('founder.first-100'),
            ],
            $channelTask,
            [
                'title' => 'Draft your first outreach message',
                'description' => 'Create one short direct-response outreach message for ' . $audience . ' that introduces the offer, speaks to the pain point, and asks for a low-friction next step.',
                'platform' => 'atlas',
                'priority' => 90,
                'cta_label' => 'Open Atlas',
                'cta_url' => route('workspace.launch', ['module' => 'atlas']),
            ],
            [
                'title' => 'Publish one proof-based marketing asset',
                'description' => 'Create one simple asset that shows the offer, the problem solved, and one piece of proof or specificity so the market can trust the promise faster.',
                'platform' => 'atlas',
                'priority' => 88,
                'cta_label' => 'Open Campaign Studio',
                'cta_url' => route('workspace.launch', ['module' => 'atlas', 'target' => '/campaign-studio']),
            ],
        ];
    }

    private function curriculumBlueprint(): array
    {
        $weeks = [
            1 => ['book' => 'Sell Like Crazy', 'theme' => 'Market pain, dream outcomes, and the core message', 'days' => [
                ['title' => 'Find the expensive problem', 'summary' => 'Choose a painful problem people already want solved.', 'lesson' => 'A founder wins faster when the offer points at a painful, urgent, and expensive problem. Do not start with features. Start with the pain your buyer already feels and wants removed.', 'application' => 'List three problems your audience pays to fix and circle the one tied to money, time, stress, or risk.'],
                ['title' => 'Define the dream outcome', 'summary' => 'Clarify the result the buyer wants most.', 'lesson' => 'People do not buy your process. They buy the result they hope life or business will look like after using you. The clearer the outcome, the easier it is to message and price.', 'application' => 'Write one sentence that begins: “My customer wants…” and make it concrete and measurable.'],
                ['title' => 'Name the audience precisely', 'summary' => 'Tight targeting makes stronger marketing and stronger offers.', 'lesson' => 'Broad audiences create weak language. A precise founder can speak to one type of buyer, one kind of context, and one moment of need. That sharpness lifts response rates.', 'application' => 'Describe the buyer by role, situation, and urgency instead of using a generic audience label.'],
                ['title' => 'Translate pain into message', 'summary' => 'Use the buyer’s pain language, not internal business language.', 'lesson' => 'Direct-response positioning lands when it mirrors what the customer is already saying in their head. Simpler language beats clever wording when it reflects the real problem and desired result.', 'application' => 'Write five phrases your buyer might say when frustrated, then reuse those phrases in your messaging.'],
                ['title' => 'Choose the single strongest promise', 'summary' => 'Lead with one promise instead of many weak ones.', 'lesson' => 'Most founders stack too many benefits into the headline. A stronger move is to choose the most valuable promise and lead with it cleanly. Clarity converts better than complexity.', 'application' => 'Write one promise statement that could sit at the top of a page or outreach message.'],
                ['title' => 'Define why you are different', 'summary' => 'Different beats better when the market is noisy.', 'lesson' => 'The market is full of “high quality” claims. Real positioning comes from a clear difference in method, speed, experience, niche, delivery, or outcome. Distinction helps memory.', 'application' => 'List three ways your offer is meaningfully different and choose the easiest one to prove.'],
                ['title' => 'Turn context into confidence', 'summary' => 'Use specifics to sound trustworthy immediately.', 'lesson' => 'Specificity lowers skepticism. Concrete words, numbers, timelines, and examples make a founder sound more credible than vague enthusiasm ever can.', 'application' => 'Rewrite your main offer statement with one number, one time frame, or one example.'],
            ]],
            2 => ['book' => 'Sell Like Crazy', 'theme' => 'Irresistible lead magnets and demand capture', 'days' => [
                ['title' => 'Why lead magnets work', 'summary' => 'Capture interest before asking for a full sale.', 'lesson' => 'Not every prospect is ready to buy now. A useful lead magnet gives them a low-friction first step while letting you begin the follow-up relationship.', 'application' => 'Choose one practical quick-win asset your audience would gladly trade contact details for.'],
                ['title' => 'Make the lead magnet outcome-specific', 'summary' => 'Offer a result, not a generic resource.', 'lesson' => 'The strongest lead magnets promise one outcome, one fix, or one shortcut. “Guide” is weak. “Checklist to stop X” or “Template to get Y” is stronger.', 'application' => 'Rename your lead magnet so the outcome is obvious from the title alone.'],
                ['title' => 'Use curiosity without being vague', 'summary' => 'A strong hook creates interest and still stays clear.', 'lesson' => 'Curiosity works best when it points toward a desirable result. Too much mystery feels gimmicky. Enough intrigue plus a clear benefit pulls the right prospect in.', 'application' => 'Write three lead magnet hooks with one benefit and one curiosity angle.'],
                ['title' => 'Reduce friction on opt-in', 'summary' => 'The easier the first step, the more leads you capture.', 'lesson' => 'Each extra field, extra click, or extra confusion lowers conversion. Lead capture should feel fast, obvious, and low-risk to the founder’s prospect.', 'application' => 'Cut your opt-in ask to the fewest pieces of information needed right now.'],
                ['title' => 'Tie the lead magnet to the core offer', 'summary' => 'Your free value should lead naturally into what you sell.', 'lesson' => 'A lead magnet should not be random content. It should attract the same type of buyer who is likely to purchase the paid solution later.', 'application' => 'Write one sentence explaining how your free asset leads logically into your offer.'],
                ['title' => 'Use proof in the opt-in message', 'summary' => 'Even a small trust signal can lift conversion.', 'lesson' => 'Prospects ask, “Why should I trust this?” Add a simple proof cue: experience, results, niche expertise, or specificity around who it is for.', 'application' => 'Add one trust signal to your lead magnet headline or form copy.'],
                ['title' => 'Build one clear call to action', 'summary' => 'A single next step beats multiple competing choices.', 'lesson' => 'Lead capture suffers when the founder gives too many options. One page, one promise, one action. That discipline improves attention and response.', 'application' => 'Rewrite your CTA so it clearly tells the prospect what happens next.'],
            ]],
            3 => ['book' => 'Sell Like Crazy', 'theme' => 'Follow-up, nurturing, and conversion sequencing', 'days' => [
                ['title' => 'The fortune is in the follow-up', 'summary' => 'Most founders quit before trust has time to build.', 'lesson' => 'Buyers often need multiple touches before they act. Follow-up is not nagging when it keeps adding clarity, trust, and reasons to move.', 'application' => 'Map out at least five touches you could send after first contact or opt-in.'],
                ['title' => 'Lead with value before urgency', 'summary' => 'Teach and help before pushing the sale hard.', 'lesson' => 'Strong follow-up earns attention by helping the buyer think better. Insight first, then invitation. This keeps the founder credible and wanted in the inbox.', 'application' => 'Draft one message that teaches a useful idea and ends with a simple CTA.'],
                ['title' => 'Use story to reduce resistance', 'summary' => 'Stories make the problem and solution easier to believe.', 'lesson' => 'A short story about a customer, situation, or founder insight can make abstract claims feel human and real. Story is one of the fastest ways to move from information to belief.', 'application' => 'Write a brief before-and-after story that illustrates the problem and the win.'],
                ['title' => 'Handle objections in sequence', 'summary' => 'Address one objection at a time across your follow-up.', 'lesson' => 'Objections rarely disappear by accident. Good nurture sequences anticipate doubt and answer it directly: timing, trust, price, need, and effort.', 'application' => 'List the top three objections and assign one objection to each next message.'],
                ['title' => 'Create urgency ethically', 'summary' => 'Urgency should clarify cost of delay, not fake scarcity.', 'lesson' => 'The buyer needs a reason to move now. Ethical urgency comes from missed opportunity, continued pain, time cost, or a real deadline tied to delivery.', 'application' => 'Write one urgency line based on what the buyer loses by waiting.'],
                ['title' => 'Ask for the next step clearly', 'summary' => 'Do not hide the invitation after giving value.', 'lesson' => 'Helpful founders still need to ask. Clear invitations make it easier for the prospect to move. Unclear asks force the buyer to guess and reduce response.', 'application' => 'Choose the one next step you want most and make that the close of your message.'],
                ['title' => 'Review the sequence for momentum', 'summary' => 'A sequence should progress, not repeat itself.', 'lesson' => 'Each touch should carry the conversation forward: new angle, new proof, new clarity, or new urgency. Repetition without progression creates fatigue.', 'application' => 'Check your sequence and note what each message uniquely adds.'],
            ]],
            4 => ['book' => 'Sell Like Crazy', 'theme' => 'Offers, risk reversal, and trust conversion', 'days' => [
                ['title' => 'Stack value before discounting', 'summary' => 'Increase perceived value before touching price.', 'lesson' => 'Founders often cut price too early. A better move is to increase value perception: clearer outcome, stronger support, added proof, or better framing of the result.', 'application' => 'List three non-discount ways to make your current offer feel more valuable.'],
                ['title' => 'Use guarantees to lower risk', 'summary' => 'Risk reversal helps the buyer say yes faster.', 'lesson' => 'A guarantee works when it removes fear without creating chaos for delivery. It tells the buyer that you believe in the result enough to share the risk.', 'application' => 'Draft one guarantee or confidence-building clause that feels bold but sustainable.'],
                ['title' => 'Proof beats persuasion', 'summary' => 'Real evidence closes more than louder claims.', 'lesson' => 'Testimonials, examples, screenshots, outcomes, or before-and-after stories lower resistance faster than founder enthusiasm alone. Proof converts skepticism into movement.', 'application' => 'Gather one piece of proof you can add to your page, pitch, or outreach today.'],
                ['title' => 'Simplify the buying path', 'summary' => 'Conversion rises when the path feels obvious.', 'lesson' => 'Too many steps, choices, and distractions lower action. Good conversion design removes friction so the prospect can move from belief to commitment smoothly.', 'application' => 'Identify the next buying step and remove one avoidable point of friction.'],
                ['title' => 'Make the cost of inaction visible', 'summary' => 'Help the buyer feel what happens if nothing changes.', 'lesson' => 'Buyers are often more motivated to avoid loss than to chase gain. When you frame the cost of staying stuck, urgency becomes easier to justify.', 'application' => 'Write two sentences showing the cost of delay in time, money, or stress.'],
                ['title' => 'Price around the result, not the hours', 'summary' => 'Outcome framing supports stronger pricing.', 'lesson' => 'If the buyer values the result, the founder should not lead with internal effort. Price is easier to defend when tied to the problem removed or result created.', 'application' => 'Rewrite your pricing explanation around outcome and value, not workload.'],
                ['title' => 'Create a decision-ready offer', 'summary' => 'The buyer should know what they get and why now.', 'lesson' => 'A decision-ready offer is clear on result, audience, proof, delivery, price, and next step. When these are foggy, the buyer delays.', 'application' => 'Audit your offer and list the single least clear part that needs rewriting.'],
            ]],
            5 => ['book' => '100M Offers', 'theme' => 'Value equation and irresistible offer design', 'days' => [
                ['title' => 'Start with the value equation', 'summary' => 'Great offers increase dream outcome and certainty while reducing time and effort.', 'lesson' => 'Hormozi’s value equation is a practical way to design offers that feel compelling. Strong offers raise the perceived result, raise belief, reduce delay, and reduce friction.', 'application' => 'Score your current offer on outcome, certainty, speed, and effort, then mark the weakest one.'],
                ['title' => 'Maximize dream outcome', 'summary' => 'Make the result more attractive and more vivid.', 'lesson' => 'A founder can raise perceived value by clarifying the win in the buyer’s language and making the result feel larger, more relevant, and more desirable.', 'application' => 'Rewrite your promise so the outcome feels more concrete and emotionally meaningful.'],
                ['title' => 'Increase perceived likelihood', 'summary' => 'Help the buyer believe this can work for them.', 'lesson' => 'Trust comes from proof, process clarity, specificity, and relevance. The more believable the path, the higher the buyer’s perceived odds of success.', 'application' => 'Add one proof asset and one process explanation to your offer.'],
                ['title' => 'Reduce time to value', 'summary' => 'Shorter paths feel safer and more attractive.', 'lesson' => 'If the buyer feels they must wait forever, motivation drops. Even when the full result takes time, highlight the early wins and first visible progress.', 'application' => 'Name the earliest useful win your customer can expect and when it happens.'],
                ['title' => 'Reduce effort and sacrifice', 'summary' => 'The easier the path feels, the more likely action becomes.', 'lesson' => 'Many offers fail because they sound exhausting. Remove unnecessary complexity and show how your offer lowers burden, confusion, or wasted effort.', 'application' => 'List the top two sacrifices buyers fear and how your offer reduces them.'],
                ['title' => 'Solve hidden problems too', 'summary' => 'A great offer handles the pains around the main pain.', 'lesson' => 'The strongest offers solve not just the headline problem, but also the annoying side problems that make buyers hesitate. This increases perceived completeness.', 'application' => 'Write down two hidden frustrations around the main problem and decide whether to solve them or address them.'],
                ['title' => 'Package the offer around certainty', 'summary' => 'Bundle components that make success feel more likely.', 'lesson' => 'A good package is not random bonuses. It is a set of components that improve certainty, speed, ease, or result. That is what makes stacking work.', 'application' => 'List three offer components that directly improve certainty or speed.'],
            ]],
            6 => ['book' => '100M Offers', 'theme' => 'Offer stacking, guarantees, naming, and price framing', 'days' => [
                ['title' => 'Stack components intentionally', 'summary' => 'Each component should make the core offer easier to buy.', 'lesson' => 'Offer stacks work when each part solves a reason the buyer might hesitate. Strong components improve onboarding, implementation, accountability, proof, or follow-through.', 'application' => 'Map each component in your offer to one objection or friction point it removes.'],
                ['title' => 'Name the mechanism', 'summary' => 'A named method makes the offer more memorable.', 'lesson' => 'Naming your approach gives the buyer a mental handle. It increases clarity and can make your offer feel more distinct than generic category language.', 'application' => 'Draft a simple name for your process, framework, or signature approach.'],
                ['title' => 'Use guarantees strategically', 'summary' => 'Guarantees are strongest when they target the real fear.', 'lesson' => 'The best guarantee is not the loudest one. It is the one that removes the main risk the buyer feels right now: trust, effort, time, or price.', 'application' => 'Write one guarantee that addresses the strongest real objection in your market.'],
                ['title' => 'Anchor price against value', 'summary' => 'Price lands better when compared to the cost of the problem.', 'lesson' => 'Buyers evaluate price in context. When you frame price against the cost of staying stuck or the size of the gain, the number becomes easier to justify.', 'application' => 'Write one sentence comparing your price to the cost of the current problem.'],
                ['title' => 'Create a reason to move now', 'summary' => 'Urgency belongs in the offer itself, not only in copy.', 'lesson' => 'A founder can create movement by limiting timing, access, bonuses, or implementation windows in a real way tied to delivery capacity or relevance.', 'application' => 'Choose one honest urgency element you can attach to the current offer.'],
                ['title' => 'Make the offer easy to explain', 'summary' => 'If it is hard to explain, it is hard to buy.', 'lesson' => 'Simple, repeatable explanations help outreach, sales, pages, and referrals. If the founder cannot explain the offer in one breath, the buyer will struggle too.', 'application' => 'Condense your offer into one sentence, one paragraph, and one short pitch.'],
                ['title' => 'Audit for irresistible value', 'summary' => 'The full offer should feel obviously worth more than the price.', 'lesson' => 'When value, clarity, certainty, and urgency work together, the buyer feels foolish not to explore the offer further. That is the standard.', 'application' => 'List the top three changes that would make your offer feel more irresistible this month.'],
            ]],
            7 => ['book' => '100M Leads', 'theme' => 'Lead generation channels, volume, and testing', 'days' => [
                ['title' => 'Pick one primary channel first', 'summary' => 'Focus beats scattered activity in early lead generation.', 'lesson' => 'Most founders spread effort across too many channels. Early momentum comes from choosing one main channel and learning how to make it work before adding more.', 'application' => 'Choose one primary acquisition channel for the next 14 days and commit to it.'],
                ['title' => 'Match channel to buyer behavior', 'summary' => 'Go where the audience already pays attention.', 'lesson' => 'The best channel is the one your specific buyer already uses while thinking about the problem you solve. Relevance beats novelty.', 'application' => 'Write where your buyer spends time before they decide to buy.'],
                ['title' => 'Volume matters in lead generation', 'summary' => 'Early lead gen is partly a game of enough attempts.', 'lesson' => 'A weak response rate may be a messaging problem, but low volume also hides signal. Founders need enough swings to learn what is working.', 'application' => 'Set a numeric outreach or publishing volume target for this week.'],
                ['title' => 'Track lead sources clearly', 'summary' => 'You cannot improve what you do not track.', 'lesson' => 'If you do not know which channels, messages, or lead sources produce replies, you cannot make sharp decisions. A founder needs simple tracking from day one.', 'application' => 'Add a clear source field or note for every new lead you create.'],
                ['title' => 'Test one variable at a time', 'summary' => 'Simple tests produce faster learning.', 'lesson' => 'When you change everything at once, you learn nothing. Better testing changes one hook, one CTA, one audience slice, or one asset at a time.', 'application' => 'Choose one small test for this week and define what success would look like.'],
                ['title' => 'Use follow-up to multiply lead value', 'summary' => 'Lead generation improves when follow-up is built in.', 'lesson' => 'A lead is more valuable when the founder has a next message, next offer, or next invitation ready. Channel performance is partly downstream from follow-up quality.', 'application' => 'Write the next step each lead should receive after the first touch.'],
                ['title' => 'Build repeatable lead routines', 'summary' => 'Routines beat random effort for pipeline consistency.', 'lesson' => 'Lead generation becomes easier when it turns into a rhythm: a daily outreach block, a publishing block, and a follow-up block. Consistency compounds.', 'application' => 'Schedule one repeatable lead-generation block in your week and protect it.'],
            ]],
            8 => ['book' => '100M Leads + 100M Deals', 'theme' => 'Conversations, qualification, closing, and weekly founder discipline', 'days' => [
                ['title' => 'Lead the conversation with diagnosis', 'summary' => 'Great sales conversations clarify the problem before pitching.', 'lesson' => 'A founder closes more when they diagnose first. Questions reveal urgency, context, cost of the problem, and whether the person is truly a fit.', 'application' => 'Write five questions you can use to diagnose before offering a solution.'],
                ['title' => 'Qualify for fit and timing', 'summary' => 'Not every lead should get the same sales energy.', 'lesson' => 'Good qualification protects time and keeps energy focused on real opportunities. Fit, urgency, authority, and willingness to act all matter.', 'application' => 'Define the three signs a lead is worth moving to a sales conversation.'],
                ['title' => 'Present the offer against the pain', 'summary' => 'Tie your solution back to the pain the buyer admitted.', 'lesson' => 'The strongest sales presentation mirrors the pain, desired result, and consequences of delay that the buyer has already said out loud.', 'application' => 'Draft a short pitch that starts with the buyer’s problem, not your offer features.'],
                ['title' => 'Handle objections calmly', 'summary' => 'Objections are often requests for more certainty.', 'lesson' => 'Price, timing, trust, and complexity objections usually mean the buyer is unconvinced about value or fit. Calm reframing and proof work better than pressure.', 'application' => 'Prepare one strong answer for each of your top three objections.'],
                ['title' => 'Ask for the decision directly', 'summary' => 'Clarity closes more often than soft ambiguity.', 'lesson' => 'Many sales are lost because the founder never truly asks. Direct, respectful closes make decisions easier and expose what the real blocker is.', 'application' => 'Write the exact sentence you will use to ask for the next commitment.'],
                ['title' => 'Review the full founder system weekly', 'summary' => 'The OS should become a weekly operating rhythm.', 'lesson' => 'A good founder reviews lessons, tasks, leads, offer, and conversion bottlenecks every week. That rhythm creates momentum and compounds learning.', 'application' => 'Create a weekly review checklist covering learning, tasks, leads, offer, and sales.'],
                ['title' => 'Build the next 90 days deliberately', 'summary' => 'Use the first 8 weeks to launch a repeatable founder rhythm.', 'lesson' => 'The end goal of the curriculum is not just understanding. It is a stronger operating system: clearer offer, better lead flow, better conversations, and more disciplined weekly execution.', 'application' => 'Write the top three systems you want Hatchers OS and Atlas to help you strengthen over the next 90 days.'],
            ]],
        ];

        $records = [];
        $sequence = 1;

        foreach ($weeks as $weekNumber => $week) {
            foreach ($week['days'] as $dayIndex => $day) {
                $dayNumber = $dayIndex + 1;
                $records[] = [
                    'week_number' => $weekNumber,
                    'day_number' => $dayNumber,
                    'sequence' => $sequence,
                    'source_book' => $week['book'],
                    'slug' => sprintf('w%d-d%d-%s', $weekNumber, $dayNumber, str($day['title'])->slug('-')),
                    'title' => sprintf('Week %d · Day %d · %s', $weekNumber, $dayNumber, $day['title']),
                    'summary' => $day['summary'],
                    'article_body' => $this->buildArticleBody($week['book'], $week['theme'], $day['lesson'], $day['application']),
                    'action_prompt' => $day['application'],
                ];
                $sequence++;
            }
        }

        return $records;
    }

    private function buildArticleBody(string $book, string $theme, string $lesson, string $application): string
    {
        return "Theme: {$theme}\n\nCore lesson:\n{$lesson}\n\nToday's application:\n{$application}\n\nFounder note:\nUse Atlas if you want help turning this lesson into sharper copy, a clearer offer, outreach, or practical execution inside Hatchers OS.";
    }
}
