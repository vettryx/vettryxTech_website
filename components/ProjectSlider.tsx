// components/ProjectSlider.tsx

'use client';

import React from 'react';
import Image from 'next/image';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

/**
 * Represents the project structure expected from the API.
 */
interface Project {
  id: number;
  title: string;
  description: string;
  image_url: string;
  link: string;
}

interface ProjectSliderProps {
  data: Project[];
}

/**
 * Renders a responsive, infinite-loop project carousel.
 * * This component handles the logic for resolving local vs external image URLs
 * and utilizes Next.js Image component for optimization where applicable.
 * * @param {ProjectSliderProps} props - The list of projects to display.
 * @returns {JSX.Element} The Swiper slider component.
 */
export default function ProjectSlider({ data }: ProjectSliderProps) {
  const API_BASE_URL = 'http://localhost:8000';

  return (
    <div className="w-full">
      <Swiper
        modules={[Navigation, Pagination, Autoplay]}
        spaceBetween={30}
        slidesPerView={1}
        navigation
        pagination={{ clickable: true }}
        loop={true}
        autoplay={{
          delay: 3000,
          disableOnInteraction: false,
          pauseOnMouseEnter: true,
        }}
        breakpoints={{
          640: { slidesPerView: 1 },
          768: { slidesPerView: 2 },
          1024: { slidesPerView: 3 },
        }}
        className="!pb-14"
      >
        {data.map((project) => {
          const isExternalLink = project.image_url.startsWith('http');
          const resolvedImageUrl = isExternalLink
            ? project.image_url
            : `${API_BASE_URL}${project.image_url}`;

          return (
            <SwiperSlide key={project.id} className="!h-auto">
              <div className="bg-white dark:bg-zinc-800 rounded-lg shadow-lg overflow-hidden h-full flex flex-col transition-transform transform hover:scale-[1.02]">
                <div className="relative h-48 w-full flex-shrink-0 bg-gray-200 dark:bg-gray-700">
                  <Image
                    src={resolvedImageUrl}
                    alt={project.title}
                    fill
                    sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
                    className="object-cover"
                    unoptimized={true} 
                  />
                </div>

                <div className="p-6 flex flex-col flex-grow">
                  <h3 className="text-xl font-bold mb-2 text-black dark:text-white">
                    {project.title}
                  </h3>

                  <p className="text-zinc-600 dark:text-zinc-400 mb-6 flex-grow text-sm leading-relaxed">
                    {project.description}
                  </p>

                  <a
                    href={project.link}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-blue-600 dark:text-blue-400 hover:text-blue-800 font-bold inline-flex items-center mt-auto transition-colors"
                  >
                    Ver Projeto &rarr;
                  </a>
                </div>
              </div>
            </SwiperSlide>
          );
        })}
      </Swiper>
    </div>
  );
}