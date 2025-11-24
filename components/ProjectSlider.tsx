// components/ProjectSlider.tsx

'use client';

import React from 'react';
import Image from 'next/image';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import { API_BASE_URL } from '../utils/api';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

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

export default function ProjectSlider({ data }: ProjectSliderProps) {
  return (
    <div className="w-full relative px-4 sm:px-12">
      <Swiper
        modules={[Navigation, Pagination, Autoplay]}
        spaceBetween={24}
        slidesPerView={1}
        navigation
        pagination={{ clickable: true }}
        loop={true}
        autoplay={{
          delay: 4000,
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
            <SwiperSlide key={project.id} className="!h-auto group">
              <a 
                href={project.link} 
                target="_blank" 
                rel="noopener noreferrer"
                className="block h-full shadow-xl transition-transform transform hover:-translate-y-2 duration-300"
              >
                <div className="bg-slate-900 h-full flex flex-col relative overflow-hidden border border-slate-700">
                  
                  <div className="relative h-56 w-full bg-slate-800">
                    <Image
                      src={resolvedImageUrl}
                      alt={project.title}
                      fill
                      className="object-cover opacity-90 group-hover:opacity-100 transition-opacity"
                      sizes="(max-width: 768px) 100vw, 33vw"
                      unoptimized={true}
                    />
                  </div>

                  <div className="bg-[#2ECC40] py-3 px-4 text-center mt-auto relative z-10">
                    <h3 className="text-[#023047] font-extrabold text-lg uppercase tracking-wider font-rajdhani">
                      {project.title}
                    </h3>
                  </div>
                </div>
              </a>
            </SwiperSlide>
          );
        })}
      </Swiper>
    </div>
  );
}