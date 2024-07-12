package com.esap_be.demo.Configs;

import com.esap_be.demo.ScheduledJobs.ClearISOCodesCache;
import com.esap_be.demo.ScheduledJobs.ISOCodesJob;
import org.quartz.JobDetail;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.scheduling.quartz.JobDetailFactoryBean;
import org.springframework.scheduling.quartz.SimpleTriggerFactoryBean;

@Configuration
public class QuartzConfig {
    @Bean
    public JobDetailFactoryBean saveIsoCodesJobDetails() {
        JobDetailFactoryBean jobDetailFactory = new JobDetailFactoryBean();
        jobDetailFactory.setJobClass(ISOCodesJob.class);
        jobDetailFactory.setDescription("Fetch and store ISO Codes 3166");
        jobDetailFactory.setDurability(true);
        return jobDetailFactory;
    }

    @Bean
    public SimpleTriggerFactoryBean isoCodesTrigger(@Qualifier("saveIsoCodesJobDetails") JobDetail jobDetail) {
        SimpleTriggerFactoryBean trigger = new SimpleTriggerFactoryBean();
        trigger.setJobDetail(jobDetail);
        trigger.setRepeatInterval(120000); // every 2 minutes
        trigger.setRepeatCount(-1); // repeat indefinitely
        return trigger;
    }

    @Bean
    public JobDetailFactoryBean clearISOCodesCacheJobDetail() {
        JobDetailFactoryBean jobDetailFactory = new JobDetailFactoryBean();
        jobDetailFactory.setJobClass(ClearISOCodesCache.class);
        jobDetailFactory.setDescription("Clear ISO Codes 3166 cache");
        jobDetailFactory.setDurability(true);
        return jobDetailFactory;
    }

    @Bean
    public SimpleTriggerFactoryBean clearISOCodesCacheTrigger(@Qualifier("clearISOCodesCacheJobDetail") JobDetail clearCacheJobDetail) {
        SimpleTriggerFactoryBean trigger = new SimpleTriggerFactoryBean();
        trigger.setJobDetail(clearCacheJobDetail);
        trigger.setRepeatInterval(90000); // every minute and a half
        trigger.setRepeatCount(-1); // repeat indefinitely
        return trigger;
    }
}
