<?php

namespace App\Modules\Careers\Database\Seeders;

use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Models\Organization;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id', 'email');

        $posts = [
            [
                'poster'       => 'hr@aqdesk.test',
                'title'        => 'Junior Laravel Developer',
                'company'      => 'CodeCraft Solutions',
                'type'         => 'job',
                'work_mode'    => 'hybrid',
                'location'     => 'Karachi, Pakistan',
                'description'  => "We are looking for a junior developer to join our web team.\n\nYou will work on client applications built with Laravel and MySQL, alongside two senior engineers who will review your code and mentor you through your first year.",
                'requirements' => "• Comfortable with PHP and at least one MVC framework\n• Understands relational database design\n• Familiar with Git\n• Fresh graduates are welcome to apply",
                'salary_min'   => 70000,
                'salary_max'   => 110000,
                'deadline'     => 30,
                'applicants'   => ['member@aqdesk.test', 'faisal@aqdesk.test'],
            ],
            [
                'poster'       => 'expert@aqdesk.test',
                'title'        => 'Research Internship — AI in Education',
                'company'      => 'National University of Sciences',
                'type'         => 'internship',
                'work_mode'    => 'onsite',
                'location'     => 'Lahore, Pakistan',
                'description'  => "A six month research internship investigating how large language models can support formative assessment in undergraduate courses.\n\nYou will help build datasets, run experiments and co-author the write-up.",
                'requirements' => "• Studying Computer Science, Data Science or a related field\n• Python and basic machine learning coursework\n• Strong written English",
                'salary_min'   => 40000,
                'salary_max'   => null,
                'deadline'     => 21,
                'applicants'   => ['sara@aqdesk.test'],
            ],
            [
                'poster'       => 'hr@aqdesk.test',
                'title'        => 'Frontend Engineer (Accessibility Focus)',
                'company'      => 'CodeCraft Solutions',
                'type'         => 'job',
                'work_mode'    => 'remote',
                'location'     => 'Remote (Pakistan)',
                'description'  => "Own the accessibility of our product front end.\n\nYou will audit existing screens against WCAG 2.1 AA, fix what you find, and set up automated checks so regressions get caught in CI.",
                'requirements' => "• Solid HTML semantics and ARIA knowledge\n• Experience with screen readers\n• JavaScript and a modern framework",
                'salary_min'   => 120000,
                'salary_max'   => 180000,
                'deadline'     => 45,
                'applicants'   => ['faisal@aqdesk.test'],
            ],
            [
                'poster'       => 'expert@aqdesk.test',
                'title'        => 'Teaching Assistant — Web Technologies',
                'company'      => 'National University of Sciences',
                'type'         => 'internship',
                'work_mode'    => 'onsite',
                'location'     => 'Lahore, Pakistan',
                'description'  => "Support the delivery of an undergraduate web technologies course: run lab sessions, mark assignments and hold weekly office hours.",
                'requirements' => "• Completed a web development course with a strong grade\n• Comfortable explaining technical ideas to first year students",
                'salary_min'   => 25000,
                'salary_max'   => null,
                'deadline'     => 14,
                'applicants'   => [],
            ],
            [
                'poster'       => 'hr@aqdesk.test',
                'title'        => 'Data Analyst Intern',
                'company'      => 'Insight Analytics',
                'type'         => 'internship',
                'work_mode'    => 'hybrid',
                'location'     => 'Islamabad, Pakistan',
                'description'  => "Work with our analytics team on dashboards and reporting for client accounts. A good first role for someone who enjoys turning messy data into clear answers.",
                'requirements' => "• SQL fundamentals\n• Excel or a BI tool\n• Python is a plus",
                'salary_min'   => 35000,
                'salary_max'   => 50000,
                'deadline'     => 25,
                'applicants'   => ['sara@aqdesk.test', 'member@aqdesk.test'],
            ],
            // Professional opportunities beyond jobs (roadmap 1.13).
            [
                'poster'       => 'expert@aqdesk.test',
                'title'        => 'Women in Computing Scholarship 2027',
                'company'      => 'National University of Sciences',
                'type'         => 'scholarship',
                'work_mode'    => 'onsite',
                'location'     => 'Lahore, Pakistan',
                'description'  => "A merit scholarship covering tuition for one academic year, with a mentor from the Computer Science faculty.\n\nFive scholarships are awarded each year.",
                'requirements' => "• Transcript of your last two semesters\n• A one-page statement on what you want to build",
                'eligibility'  => 'Women enrolled in a BS Computer Science, Software Engineering or Data Science programme in Pakistan, with a CGPA of 3.0 or higher.',
                'salary_min'   => 150000,
                'salary_max'   => null,
                'deadline'     => 40,
                'applicants'   => ['sara@aqdesk.test'],
            ],
            [
                'poster'       => 'expert@aqdesk.test',
                'title'        => 'Research Fellowship — Accessible Learning Technology',
                'company'      => 'National University of Sciences',
                'type'         => 'fellowship',
                'work_mode'    => 'hybrid',
                'location'     => 'Lahore, Pakistan',
                'description'  => "A twelve month fellowship in the Learning Technology Lab, studying how assistive technology users experience online courses.",
                'requirements' => "• A research proposal of up to two pages\n• Two references",
                'eligibility'  => 'Recent graduates (within two years) in Computer Science, HCI or Education.',
                'salary_min'   => 90000,
                'salary_max'   => null,
                'deadline'     => 35,
                'starts_on'    => 75,
                'ends_on'      => 440,
                'applicants'   => [],
            ],
            [
                'poster'       => 'hr@aqdesk.test',
                'title'        => 'Student Innovation Grant',
                'company'      => 'Insight Analytics',
                'type'         => 'grant',
                'work_mode'    => 'remote',
                'location'     => 'Pakistan',
                'description'  => "Seed funding for student teams turning a data project into a working product for a local community or NGO.",
                'requirements' => "• A short project plan and budget\n• A letter from your supervisor",
                'eligibility'  => 'Teams of 2–4 students from any Pakistani university.',
                'salary_min'   => 250000,
                'salary_max'   => 500000,
                'deadline'     => 28,
                'applicants'   => ['member@aqdesk.test'],
            ],
            [
                'poster'       => 'hr@aqdesk.test',
                'title'        => 'Hack Weekend: AI for Public Services',
                'company'      => 'CodeCraft Solutions',
                'type'         => 'hackathon',
                'work_mode'    => 'hybrid',
                'location'     => 'Karachi, Pakistan',
                'description'  => "48 hours to prototype AI tools that make public services easier to use. Mentors on site, meals provided, demo day on Sunday afternoon.",
                'requirements' => null,
                'eligibility'  => 'Students and graduates; teams of 2–4. Solo participants are matched with a team.',
                'salary_min'   => 300000,
                'salary_max'   => null,
                'deadline'     => 20,
                'starts_on'    => 30,
                'ends_on'      => 32,
                'applicants'   => ['faisal@aqdesk.test'],
            ],
            [
                'poster'       => 'expert@aqdesk.test',
                'title'        => 'Call for Papers — Workshop on AI in Education',
                'company'      => 'National University of Sciences',
                'type'         => 'call_for_papers',
                'work_mode'    => 'onsite',
                'location'     => 'Lahore, Pakistan',
                'description'  => "Short papers (4–6 pages) on AI-supported teaching, assessment and accessibility in South Asian higher education. Accepted papers are presented at a one-day workshop.",
                'requirements' => "• Anonymous submission in the workshop template\n• Original work not under review elsewhere",
                'eligibility'  => 'Researchers and students; student-led papers are especially welcome.',
                'salary_min'   => null,
                'salary_max'   => null,
                'deadline'     => 50,
                'starts_on'    => 90,
                'ends_on'      => 90,
                'applicants'   => [],
            ],
        ];

        // Organization profiles for the companies above (roadmap 1.12).
        $profiles = [
            'CodeCraft Solutions' => ['hr@aqdesk.test', 'Web and mobile products for clients across Pakistan', 'Software', 'Karachi, Pakistan', '51-200',
                "CodeCraft builds web and mobile products for retail, health and public-sector clients. We hire graduates every year and run an in-house mentoring programme."],
            'National University of Sciences' => ['expert@aqdesk.test', 'Research and teaching in computing and engineering', 'Higher education', 'Lahore, Pakistan', '1000+',
                "The School of Computing runs undergraduate and postgraduate programmes and four research labs, including the Learning Technology Lab."],
            'Insight Analytics' => ['hr@aqdesk.test', 'Dashboards and data products for growing companies', 'Data & analytics', 'Islamabad, Pakistan', '11-50',
                "A small analytics consultancy helping companies and NGOs make decisions with their own data."],
        ];
        $organizations = [];
        foreach ($profiles as $name => [$owner, $tagline, $industry, $location, $size, $about]) {
            $organizations[$name] = Organization::updateOrCreate(['slug' => Str::slug($name)], [
                'user_id' => $users[$owner], 'name' => $name, 'tagline' => $tagline, 'about' => $about,
                'website' => 'https://'.Str::slug($name).'.example.org', 'location' => $location,
                'industry' => $industry, 'size' => $size,
            ]);
        }

        $coverLetters = [
            "I am in the final year of my Computer Science degree and have built several Laravel applications, including a community knowledge platform with role-based access, a Q&A module and a jobs board.\n\nI am comfortable with MySQL schema design and have used Git throughout my final year project. I would value the mentoring this role offers and can start immediately after my submission deadline.",
            "My coursework and personal projects have focused on exactly the area this role covers, and I have been reading around the subject well beyond what my degree requires.\n\nI have attached my CV and would welcome the chance to talk about how I could contribute.",
            "I care a great deal about building interfaces that everyone can use, and I have spent the last year auditing my own projects against WCAG and fixing what I found.\n\nI would like to do that work full time, and I would bring both the technical skills and the habit of testing with a keyboard and a screen reader rather than assuming.",
        ];

        foreach ($posts as $index => $data) {
            $job = JobPost::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'user_id'         => $users[$data['poster']],
                    'organization_id' => $organizations[$data['company']]->id,
                    'title'           => $data['title'],
                    'company'         => $data['company'],
                    'type'            => $data['type'],
                    'work_mode'       => $data['work_mode'],
                    'location'        => $data['location'],
                    'description'     => $data['description'],
                    'requirements'    => $data['requirements'],
                    'eligibility'     => $data['eligibility'] ?? null,
                    'salary_min'      => $data['salary_min'],
                    'salary_max'      => $data['salary_max'],
                    'deadline'        => now()->addDays($data['deadline'])->toDateString(),
                    'starts_on'       => isset($data['starts_on']) ? now()->addDays($data['starts_on'])->toDateString() : null,
                    'ends_on'         => isset($data['ends_on']) ? now()->addDays($data['ends_on'])->toDateString() : null,
                    'is_active'       => true,
                ]
            );

            // created_at is guarded (not in $fillable), so it is set directly.
            $job->created_at = now()->subDays(count($posts) - $index);
            $job->save();

            foreach ($data['applicants'] as $i => $email) {
                $application = JobApplication::updateOrCreate(
                    [
                        'job_post_id' => $job->id,
                        'user_id'     => $users[$email],
                    ],
                    [
                        'cover_letter' => $coverLetters[$i % count($coverLetters)],
                        'status'       => ['pending', 'reviewed', 'shortlisted'][$i % 3],
                    ]
                );

                $application->created_at = $job->created_at->copy()->addDays(1);
                $application->save();

                // Its history (roadmap 1.14): submitted, then reviewed by the poster.
                if ($application->statusChanges()->doesntExist()) {
                    $application->statusChanges()->create(['user_id' => $application->user_id, 'to_status' => 'pending'])
                        ->forceFill(['created_at' => $application->created_at])->save();
                    if ($application->status !== 'pending') {
                        $application->statusChanges()->create([
                            'user_id' => $job->user_id, 'from_status' => 'pending', 'to_status' => $application->status,
                            'message' => 'Thanks for applying. We are reading every application and will reply within two weeks.',
                        ]);
                    }
                }
            }
        }
    }
}
