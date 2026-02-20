<?php

namespace Database\Seeders;

use App\Models\EmergencyCategory;
use App\Models\EmergencyGuide;
use Illuminate\Database\Seeder;

class EmergencyGuideSeeder extends Seeder
{
    public function run(): void
    {
        $guides = [
            // Injuries
            [
                'category_slug' => 'injuries',
                'title' => 'How to Stop Bleeding',
                'slug' => 'how-to-stop-bleeding',
                'summary' => 'Learn how to control bleeding from cuts and wounds on your pet.',
                'content' => $this->getBleedingContent(),
                'applicable_species' => ['dog', 'cat'],
                'severity_level' => 'high',
                'estimated_read_minutes' => 5,
            ],
            [
                'category_slug' => 'injuries',
                'title' => 'Broken Bone First Aid',
                'slug' => 'broken-bone-first-aid',
                'summary' => 'Immediate steps to take if you suspect your pet has a broken bone.',
                'content' => $this->getBrokenBoneContent(),
                'applicable_species' => ['dog', 'cat', 'rabbit'],
                'severity_level' => 'critical',
                'estimated_read_minutes' => 6,
            ],
            // Poisoning
            [
                'category_slug' => 'poisoning',
                'title' => 'Common Toxic Foods for Pets',
                'slug' => 'toxic-foods-pets',
                'summary' => 'Foods that are dangerous for dogs and cats and what to do if ingested.',
                'content' => $this->getToxicFoodsContent(),
                'applicable_species' => ['dog', 'cat'],
                'severity_level' => 'critical',
                'estimated_read_minutes' => 8,
            ],
            [
                'category_slug' => 'poisoning',
                'title' => 'Household Chemical Poisoning',
                'slug' => 'household-chemical-poisoning',
                'summary' => 'What to do if your pet ingests cleaning products or chemicals.',
                'content' => $this->getChemicalPoisoningContent(),
                'applicable_species' => ['dog', 'cat', 'bird', 'rabbit'],
                'severity_level' => 'critical',
                'estimated_read_minutes' => 6,
            ],
            // Breathing Problems
            [
                'category_slug' => 'breathing-problems',
                'title' => 'Pet Choking - Heimlich Maneuver',
                'slug' => 'pet-choking-heimlich',
                'summary' => 'How to perform the Heimlich maneuver on dogs and cats.',
                'content' => $this->getChokingContent(),
                'applicable_species' => ['dog', 'cat'],
                'severity_level' => 'critical',
                'estimated_read_minutes' => 5,
            ],
            // Seizures
            [
                'category_slug' => 'seizures',
                'title' => 'What to Do During a Pet Seizure',
                'slug' => 'pet-seizure-response',
                'summary' => 'How to keep your pet safe during and after a seizure.',
                'content' => $this->getSeizureContent(),
                'applicable_species' => ['dog', 'cat'],
                'severity_level' => 'high',
                'estimated_read_minutes' => 5,
            ],
            // Heat & Cold
            [
                'category_slug' => 'heat-cold',
                'title' => 'Heatstroke in Pets',
                'slug' => 'heatstroke-pets',
                'summary' => 'Recognizing and treating heatstroke in dogs and cats.',
                'content' => $this->getHeatstrokeContent(),
                'applicable_species' => ['dog', 'cat', 'rabbit'],
                'severity_level' => 'critical',
                'estimated_read_minutes' => 6,
            ],
            // Digestive Issues
            [
                'category_slug' => 'digestive-issues',
                'title' => 'Bloat in Dogs - Emergency Response',
                'slug' => 'bloat-dogs-emergency',
                'summary' => 'Recognizing and responding to gastric dilatation-volvulus (GDV).',
                'content' => $this->getBloatContent(),
                'applicable_species' => ['dog'],
                'severity_level' => 'critical',
                'estimated_read_minutes' => 5,
            ],
        ];

        foreach ($guides as $guideData) {
            $category = EmergencyCategory::where('slug', $guideData['category_slug'])->first();

            if ($category) {
                EmergencyGuide::updateOrCreate(
                    ['slug' => $guideData['slug']],
                    [
                        'category_id' => $category->id,
                        'title' => $guideData['title'],
                        'slug' => $guideData['slug'],
                        'summary' => $guideData['summary'],
                        'content' => $guideData['content'],
                        'applicable_species' => $guideData['applicable_species'],
                        'severity_level' => $guideData['severity_level'],
                        'estimated_read_minutes' => $guideData['estimated_read_minutes'],
                        'is_published' => true,
                    ]
                );
            }
        }
    }

    private function getBleedingContent(): string
    {
        return <<<'CONTENT'
## Immediate Steps

1. **Stay calm** - Your pet can sense your stress
2. **Apply pressure** - Use a clean cloth or gauze and apply firm, direct pressure to the wound
3. **Elevate if possible** - If the wound is on a limb, try to elevate it above heart level
4. **Maintain pressure** - Keep pressure on for at least 5-10 minutes without checking

## Warning Signs

- Blood soaking through multiple cloths
- Spurting blood (arterial bleeding)
- Pet becoming weak or pale gums
- Bleeding that won't stop after 15 minutes

## When to Seek Emergency Care

Seek immediate veterinary care if:
- The wound is deep or gaping
- Bleeding doesn't slow after 15 minutes of pressure
- The wound was caused by a bite from another animal
- There is visible bone or muscle

## Do NOT

- Do not use a tourniquet unless trained
- Do not remove embedded objects
- Do not apply hydrogen peroxide to deep wounds
CONTENT;
    }

    private function getBrokenBoneContent(): string
    {
        return <<<'CONTENT'
## Signs of a Broken Bone

- Limping or refusing to bear weight
- Swelling at the injury site
- Visible deformity
- Crying or whimpering when touched
- Guarding the affected area

## Immediate Steps

1. **Keep your pet still** - Movement can worsen the injury
2. **Do NOT attempt to set the bone**
3. **Muzzle your pet** - Even gentle pets may bite when in pain
4. **Create a makeshift stretcher** - Use a board, blanket, or towel
5. **Stabilize the limb** - If possible, loosely wrap with a towel or bandage without manipulating

## Transportation

- Support the injured area during transport
- Keep your pet as still as possible
- Drive carefully to avoid jarring movements

## Seek Veterinary Care Immediately

All suspected fractures require professional treatment with X-rays and proper stabilization.
CONTENT;
    }

    private function getToxicFoodsContent(): string
    {
        return <<<'CONTENT'
## Dangerous Foods for Dogs

- **Chocolate** - Contains theobromine; darker = more dangerous
- **Grapes and raisins** - Can cause kidney failure
- **Onions and garlic** - Damage red blood cells
- **Xylitol** - Sugar substitute found in gum, candy, peanut butter
- **Macadamia nuts** - Cause weakness and vomiting
- **Alcohol** - Even small amounts are dangerous
- **Caffeine** - Found in coffee, tea, energy drinks

## Dangerous Foods for Cats

- All of the above, plus:
- **Raw eggs** - Risk of salmonella
- **Raw fish** - Can cause thiamine deficiency
- **Milk and dairy** - Many cats are lactose intolerant

## If Your Pet Ate Something Toxic

1. **Note what they ate and how much**
2. **Call your vet or pet poison control immediately**
3. **Do NOT induce vomiting** unless directed by a professional
4. **Bring the packaging** if available

## Emergency Contacts

Keep these numbers saved:
- Your regular vet
- Nearest emergency animal hospital
- ASPCA Poison Control: (888) 426-4435
CONTENT;
    }

    private function getChemicalPoisoningContent(): string
    {
        return <<<'CONTENT'
## Common Household Toxins

- Cleaning products (bleach, detergents)
- Antifreeze (extremely dangerous - sweet taste attracts pets)
- Pesticides and rodenticides
- Medications (human and pet overdoses)
- Essential oils (especially toxic to cats)

## Signs of Chemical Poisoning

- Drooling or foaming at the mouth
- Vomiting or diarrhea
- Difficulty breathing
- Seizures or tremors
- Burns around the mouth
- Unusual behavior or lethargy

## Immediate Actions

1. **Remove your pet from the source**
2. **Do NOT induce vomiting** - Some chemicals cause more damage coming back up
3. **Collect product information** - Name, active ingredients, amount ingested
4. **Call poison control or your vet immediately**

## For Skin Exposure

- Rinse with lukewarm water for 15-20 minutes
- Use mild dish soap if recommended
- Do not use solvents or chemicals to clean

## Important

Never give home remedies without professional guidance. What works for humans may be dangerous for pets.
CONTENT;
    }

    private function getChokingContent(): string
    {
        return <<<'CONTENT'
## Signs of Choking

- Pawing at the mouth
- Gagging or retching
- Blue-tinged gums or tongue
- Panicked behavior
- Difficulty breathing or no breathing

## For Small Dogs and Cats

1. **Open the mouth** - Look for visible objects
2. **Sweep carefully** - Use your finger to remove objects you can see (be careful not to push deeper)
3. **If unsuccessful**, hold pet upside down by the hips and shake gently
4. **Apply abdominal thrusts** - Place hands below rib cage, push up and forward

## For Large Dogs

1. **Check mouth** - Open and look for obstruction
2. **Stand behind your dog** - Place arms around the waist
3. **Make a fist** - Place it just behind the ribs
4. **Thrust upward** - 5 quick compressions

## After the Object is Removed

- Check for injuries in the mouth and throat
- Monitor breathing
- Seek veterinary care even if successful

## Prevention

- Avoid toys small enough to swallow
- Supervise chewing and play
- Cut food into appropriate sizes
CONTENT;
    }

    private function getSeizureContent(): string
    {
        return <<<'CONTENT'
## During the Seizure

1. **Stay calm**
2. **Do NOT restrain your pet** - You cannot stop a seizure
3. **Clear the area** - Move furniture and objects away
4. **Do NOT put anything in their mouth** - They cannot swallow their tongue
5. **Time the seizure** - Important information for the vet

## Protect Your Pet

- Keep hands away from the mouth
- Gently prevent them from falling off furniture
- Dim lights if possible
- Reduce noise

## After the Seizure

- Speak softly and comfort your pet
- Keep them warm with a blanket
- Do not offer food or water immediately
- Allow them to recover in a quiet space

## When to Seek Emergency Care

- Seizure lasts longer than 5 minutes
- Multiple seizures in a row (cluster seizures)
- First-time seizure
- Your pet doesn't recover within 30 minutes
- Seizure occurs while swimming

## Important Information to Track

- Length of seizure
- What happened before and after
- Any unusual behavior
- Time between seizures if multiple
CONTENT;
    }

    private function getHeatstrokeContent(): string
    {
        return <<<'CONTENT'
## Signs of Heatstroke

- Heavy panting
- Excessive drooling
- Bright red tongue and gums
- Vomiting or diarrhea
- Stumbling or collapse
- Body temperature above 104F (40C)

## Immediate Cooling Steps

1. **Move to a cool area** - Shade or air conditioning
2. **Apply cool (not cold) water** - Focus on neck, armpits, groin
3. **Use a fan** - Air movement helps cooling
4. **Offer small amounts of cool water** - Do not force drinking
5. **Do NOT use ice** - Can cause blood vessels to constrict

## Monitor Temperature

- Check temperature rectally every 5 minutes
- Stop cooling at 103F (39.4C)
- Continue to monitor

## Seek Veterinary Care

Heatstroke can cause:
- Organ damage
- Blood clotting issues
- Brain damage

Even if your pet appears to recover, internal damage may have occurred.

## Prevention

- Never leave pets in cars
- Limit exercise on hot days
- Provide shade and water
- Watch brachycephalic breeds (flat-faced) closely
CONTENT;
    }

    private function getBloatContent(): string
    {
        return <<<'CONTENT'
## What is Bloat (GDV)?

Gastric dilatation-volvulus is a life-threatening emergency where the stomach fills with gas and may twist. This is one of the most serious emergencies in dogs.

## Warning Signs

- Distended or swollen abdomen
- Unproductive retching (trying to vomit but nothing comes up)
- Restlessness and pacing
- Excessive drooling
- Rapid breathing
- Weak pulse
- Pale gums

## Immediate Action

**This is a medical emergency. Do not wait.**

1. Call your emergency vet immediately
2. Do not attempt home treatment
3. Transport your dog as quickly and safely as possible

## Risk Factors

- Large, deep-chested breeds
- Eating one large meal per day
- Eating rapidly
- Exercise after eating
- Age (risk increases with age)

## Prevention Tips

- Feed 2-3 smaller meals instead of one large meal
- Use slow-feeder bowls
- Avoid exercise 1-2 hours after eating
- Consider preventive surgery for high-risk breeds (gastropexy)

## Time is Critical

Dogs can die from bloat within hours. If you suspect bloat, treat it as the emergency it is.
CONTENT;
    }
}
