<?php
// Start session to maintain state
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.3/cdn.min.js"></script>
    <style>
        .dot-pattern {
            position: fixed; /* Changed from absolute to fixed */
            inset: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle, rgba(139, 92, 246, 0.4) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
            z-index: 0;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .team-card {
            transition: all 0.3s ease;
        }
        
        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .team-card .member-image {
            transition: transform 0.5s ease;
        }
        
        .team-card:hover .member-image {
            transform: scale(1.05);
        }

        .social-icon {
            transition: all 0.2s ease;
        }
        
        .social-icon:hover {
            transform: translateY(-3px);
        }

        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .appear {
            opacity: 1;
            transform: translateY(0);
        }
        
        .back-button {
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            transform: translateX(-5px);
        }
        body::-webkit-scrollbar{
            display: none;
        }
    </style>
</head>
<body class="min-h-screen" x-data="{ 
    team: [
        {
            name: 'Sarah Johnson',
            role: 'CEO & Founder',
            bio: 'With over 15 years of experience in education technology, Sarah leads our vision for transforming digital learning.',
            image: 'https://via.placeholder.com/300x300',
            socials: {
                linkedin: '#',
                twitter: '#',
                email: 'sarah@example.com'
            }
        },
        {
            name: 'Michael Chen',
            role: 'Chief Technology Officer',
            bio: 'Michael brings 12 years of software engineering expertise, specializing in secure assessment platforms.',
            image: 'https://via.placeholder.com/300x300',
            socials: {
                linkedin: '#',
                github: '#',
                email: 'michael@example.com'
            }
        },
        {
            name: 'Priya Patel',
            role: 'Head of Education',
            bio: 'Former university professor with a passion for making education accessible and engaging for all students.',
            image: 'https://via.placeholder.com/300x300',
            socials: {
                linkedin: '#',
                twitter: '#',
                email: 'priya@example.com'
            }
        },
        {
            name: 'James Wilson',
            role: 'UX Designer',
            bio: 'James creates intuitive and accessible interfaces that help students focus on learning, not technology.',
            image: 'https://via.placeholder.com/300x300',
            socials: {
                linkedin: '#',
                dribbble: '#',
                email: 'james@example.com'
            }
        },
        {
            name: 'Aisha Bello',
            role: 'Customer Success Manager',
            bio: 'Dedicated to ensuring both students and educators have seamless experiences with our platform.',
            image: 'https://via.placeholder.com/300x300',
            socials: {
                linkedin: '#',
                twitter: '#',
                email: 'aisha@example.com'
            }
        },
        {
            name: 'Carlos Rodriguez',
            role: 'Security Specialist',
            bio: 'Expert in protecting sensitive educational data and ensuring our exam platform meets the highest security standards.',
            image: 'https://via.placeholder.com/300x300',
            socials: {
                linkedin: '#',
                github: '#',
                email: 'carlos@example.com'
            }
        }
    ]
}" x-init="
    setTimeout(() => {
        document.querySelectorAll('.fade-in').forEach((el, i) => {
            setTimeout(() => {
                el.classList.add('appear');
            }, i * 150);
        });
    }, 100);
">
    <!-- Background gradient -->
    <div class="fixed inset-0 bg-gradient-to-br from-purple-100 to-sky-100 z-0"></div>
    
    <!-- Dot pattern -->
    <div class="dot-pattern"></div>
    
    <!-- Back Button -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <a href="../" class="flex items-center text-purple-900 hover:text-purple-700 font-medium back-button">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back to Homepage
        </a>
    </div>
    
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="glass rounded-xl p-8 shadow-lg mb-12 fade-in">
            <h1 class="text-4xl font-bold text-purple-900 text-center mb-4">About Our Team</h1>
            <p class="text-purple-800 text-center max-w-3xl mx-auto">
                We're a dedicated team of educators, technologists, and innovators committed to transforming the online examination experience. Our platform combines security, accessibility, and ease of use to create the ideal environment for students and educators alike.
            </p>
        </div>
        
        <!-- Team Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <template x-for="(member, index) in team" :key="index">
                <div class="glass rounded-xl overflow-hidden shadow-lg team-card fade-in">
                    <div class="overflow-hidden h-64">
                        <img :src="member.image" :alt="'Photo of ' + member.name" class="w-full h-full object-cover member-image">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-purple-900" x-text="member.name"></h3>
                        <p class="text-purple-700 mb-3" x-text="member.role"></p>
                        <p class="text-purple-800 mb-4" x-text="member.bio"></p>
                        
                        <!-- Social Icons -->
                        <div class="flex space-x-4 mt-4">
                            <template x-if="member.socials.linkedin">
                                <a :href="member.socials.linkedin" class="text-purple-600 hover:text-purple-800 social-icon">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.454C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/>
                                    </svg>
                                </a>
                            </template>
                            <template x-if="member.socials.twitter">
                                <a :href="member.socials.twitter" class="text-purple-600 hover:text-purple-800 social-icon">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                                    </svg>
                                </a>
                            </template>
                            <template x-if="member.socials.github">
                                <a :href="member.socials.github" class="text-purple-600 hover:text-purple-800 social-icon">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                            </template>
                            <template x-if="member.socials.dribbble">
                                <a :href="member.socials.dribbble" class="text-purple-600 hover:text-purple-800 social-icon">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c5.51 0 10-4.48 10-10S17.51 2 12 2zm6.605 4.61a8.502 8.502 0 011.93 5.314c-.281-.054-3.101-.629-5.943-.271-.065-.141-.12-.293-.184-.445a25.416 25.416 0 00-.564-1.236c3.145-1.28 4.577-3.124 4.761-3.362zM12 3.475c2.17 0 4.154.813 5.662 2.148-.152.216-1.443 1.941-4.48 3.08-1.399-2.57-2.95-4.675-3.189-5A8.687 8.687 0 0112 3.475zm-3.633.803a53.896 53.896 0 013.167 4.935c-3.992 1.063-7.517 1.04-7.896 1.04a8.581 8.581 0 014.729-5.975zM3.453 12.01v-.21c.37.01 4.512.065 8.775-1.215.25.477.477.965.694 1.453-.109.033-.228.065-.336.098-4.404 1.42-6.747 5.303-6.942 5.629a8.522 8.522 0 01-2.19-5.705zM12 20.547a8.482 8.482 0 01-5.239-1.8c.152-.315 1.888-3.656 6.703-5.337.022-.01.033-.01.054-.022a35.318 35.318 0 011.823 6.475 8.4 8.4 0 01-3.341.684zm4.761-1.465c-.086-.52-.542-3.015-1.659-6.084 2.679-.423 5.022.271 5.314.369a8.468 8.468 0 01-3.655 5.715z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                            </template>
                            <template x-if="member.socials.email">
                                <a :href="'mailto:' + member.socials.email" class="text-purple-600 hover:text-purple-800 social-icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Mission Section -->
        <div class="glass rounded-xl p-8 shadow-lg mt-12 fade-in">
            <h2 class="text-3xl font-bold text-purple-900 text-center mb-6">Our Mission</h2>
            <div class="max-w-3xl mx-auto">
                <p class="text-purple-800 mb-4">
                    At ExamPortal, we believe education should be accessible, fair, and secure for everyone. Our mission is to provide a reliable platform that enables educators to create meaningful assessments and allows students to demonstrate their knowledge in a supportive digital environment.
                </p>
                <p class="text-purple-800 mb-4">
                    We're committed to continuous improvement and innovation in the educational technology space. By listening to feedback from our community of educators and students, we're constantly refining our platform to meet the evolving needs of modern education.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="relative z-10 glass mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex justify-center md:justify-start">
                    <span class="text-purple-900 font-bold">RUPP Examination</span>
                </div>
                <div class="mt-4 md:mt-0">
                    <p class="text-center text-purple-800 md:text-right">
                        &copy; 2025 RUPP E5 Year4. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>